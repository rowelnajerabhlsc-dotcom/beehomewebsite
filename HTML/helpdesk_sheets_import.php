<?php
/**
 * helpdesk_sheets_import.php
 * Pulls new Google Form submissions from the response Sheet and
 * creates helpdesk_cases rows for anything not yet imported.
 * Called at the top of helpdesk_dashboard.php on every page load.
 *
 * Requires: composer require google/apiclient
 * Config needed in config.php:
 *   $google_service_account_path  - path to the service account JSON,
 *                                    stored outside the web root
 *   $google_sheet_id              - the spreadsheet ID (from its URL)
 *   $google_sheet_range           - e.g. 'Form Responses 1!A2:N'
 *                                    (starts at row 2 to skip the header)
 *
 * Column order in the sheet (A-N), fixed per the live Google Form:
 *   A Timestamp
 *   B Email Address
 *   C Pahayag ng Pagpapatunay at Pag-sang-ayon (Declaration/Consent) — not stored
 *   D First Name
 *   E Middle Name
 *   F Last Name
 *   G Kasarian (Sex)
 *   H Contact Number
 *   I Tirahan (Address)
 *   J Project Location
 *   K Uri ng Hinaing (Complaint / Request / Inquiry)
 *   L Paglalarawan ng reklamo... (Description)
 *   M Kabuuang Halaga (Amount, optional)
 *   N Dokumento o Larawan (Attachment link, optional)
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/helpdesk_draft_generator.php';

function importNewSubmissionsFromSheet(mysqli $conn): array {
    global $google_service_account_path, $google_sheet_id, $google_sheet_range;

    $result = ['imported' => 0, 'skipped' => 0, 'errors' => []];

    if (empty($google_service_account_path) || !file_exists($google_service_account_path)) {
        $result['errors'][] = 'Service account key not configured or not found.';
        return $result;
    }
    if (empty($google_sheet_id)) {
        $result['errors'][] = 'Google Sheet ID not configured.';
        return $result;
    }

    try {
        $client = new Google\Client();
        $client->setAuthConfig($google_service_account_path);
        $client->addScope(Google\Service\Sheets::SPREADSHEETS_READONLY);

        $service = new Google\Service\Sheets($client);
        $range = $google_sheet_range ?: 'Form Responses 1!A2:N';

        $response = $service->spreadsheets_values->get($google_sheet_id, $range);
        $rows = $response->getValues();
    } catch (Exception $e) {
        error_log("Sheets import failed to fetch: " . $e->getMessage());
        $result['errors'][] = 'Could not reach the Google Sheet. Check API access.';
        return $result;
    }

    if (empty($rows)) {
        return $result; // nothing to import
    }

    // Existing sheet_row_number values already imported, so we never
    // duplicate a case for the same row.
    $existing = [];
    $stmt = $conn->prepare("SELECT sheet_row_number FROM helpdesk_cases");
    $stmt->execute();
    $existingRes = $stmt->get_result();
    while ($r = $existingRes->fetch_assoc()) {
        $existing[(int)$r['sheet_row_number']] = true;
    }
    $existingRes->free();
    $stmt->close();

    // Range starts at row 2 (header skipped), so row index 0 in $rows = sheet row 2.
    $startRow = 2;

    foreach ($rows as $i => $row) {
        $sheetRowNumber = $startRow + $i;

        if (isset($existing[$sheetRowNumber])) {
            $result['skipped']++;
            continue;
        }

        // Pad row in case trailing empty cells were dropped by the API.
        $row = array_pad($row, 14, '');

        $timestamp    = trim($row[0]);
        $email        = trim($row[1]);
        // $row[2] declaration/consent — not stored
        $firstName    = trim($row[3]);
        $middleName   = trim($row[4]);
        $lastName     = trim($row[5]);
        $sex          = trim($row[6]);
        $contact      = trim($row[7]);
        $address      = trim($row[8]);
        $location     = trim($row[9]);
        $requestType  = trim($row[10]);
        $details      = trim($row[11]);
        $amountRaw    = trim($row[12]);
        $attachment   = trim($row[13]);

        $fullName = trim(preg_replace('/\s+/', ' ', "{$firstName} {$middleName} {$lastName}"));

        if ($firstName === '' || $email === '') {
            $result['errors'][] = "Row {$sheetRowNumber} skipped: missing name or email.";
            $result['skipped']++;
            continue;
        }

        if (!in_array($requestType, ['Complaint', 'Request', 'Inquiry'], true)) {
            $requestType = 'Inquiry'; // fallback if the form value doesn't match exactly
        }

        $amount = ($amountRaw !== '') ? (float)preg_replace('/[^0-9.]/', '', $amountRaw) : null;

        // Parse the form's timestamp for sheet_submitted_at; fall back to NULL
        // if the format is unexpected rather than failing the whole import.
        $submittedAt = null;
        $parsed = strtotime($timestamp);
        if ($parsed !== false) {
            $submittedAt = date('Y-m-d H:i:s', $parsed);
        }

        $refTimestamp = date('YmdHis');
        $referenceNumber = "BH-{$refTimestamp}-{$sheetRowNumber}";

        $stmt = $conn->prepare(
            "INSERT INTO helpdesk_cases
                (reference_number, sheet_row_number, sheet_submitted_at,
                 member_name, member_email, member_contact, member_address, member_sex,
                 project_location, request_type, request_details, claim_amount, attachment_url,
                 status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', NOW())"
        );
        $stmt->bind_param(
            "sisssssssssds",
            $referenceNumber, $sheetRowNumber, $submittedAt,
            $fullName, $email, $contact, $address, $sex,
            $location, $requestType, $details, $amount, $attachment
        );

        if ($stmt->execute()) {
            $newCaseId = $stmt->insert_id;
            $result['imported']++;

            $caseForDraft = [
                'reference_number' => $referenceNumber,
                'request_type'     => $requestType,
                'request_details'  => $details,
                'created_at'       => date('Y-m-d H:i:s'),
            ];
            generateInitialDraft($conn, $newCaseId, $caseForDraft);
        } else {
            $result['errors'][] = "Row {$sheetRowNumber} failed to insert: " . $stmt->error;
        }
    }

    return $result;
}
