<?php
/**
 * helpdesk_draft_generator.php
 * Builds the acknowledgement-receipt email from case data + editable
 * fields, and creates the initial draft version at import time.
 *
 * Editable fields (admin-filled, blank in the Google Form template):
 *   classification   - 'Simple' or 'Complex'
 *   sla_days         - number of days for the SLA line
 *   contact_person   - named contact for follow-up
 *   contact_info     - phone/email for follow-up
 *   staff_name       - name of the authorized staff signing the email
 *
 * Auto-filled from case data (not editable):
 *   date received, reference number, request type, concern summary
 */

function defaultEditableFields(): array {
    return [
        'classification' => 'Simple',
        'sla_days'       => '15',
        'contact_person' => '',
        'contact_info'   => '',
        'staff_name'     => '',
    ];
}

function renderCaseEmailBody(array $case, array $fields): string {
    $fields = array_merge(defaultEditableFields(), $fields);

    $type          = $case['request_type'] ?? '';
    $dateReceived  = !empty($case['created_at']) ? date('F j, Y', strtotime($case['created_at'])) : date('F j, Y');
    $ref           = $case['reference_number'] ?? '';
    $summary       = $case['request_details'] ?? '';

    $classification = $fields['classification'];
    $checkSimple  = ($classification === 'Simple')  ? '[x]' : '[ ]';
    $checkComplex = ($classification === 'Complex') ? '[x]' : '[ ]';

    $slaDays       = $fields['sla_days'];
    $contactPerson = $fields['contact_person'];
    $contactInfo   = $fields['contact_info'];
    $staffName     = $fields['staff_name'];

    return <<<TEXT
BEE HOME LABOR MULTIPURPOSE COOPERATIVE
KUMPIRMASYON NG PAGTANGGAP (ACKNOWLEDGEMENT RECEIPT)
{$type}

Petsa ng Pagtanggap: {$dateReceived}
Reference Number: {$ref}

Mahal naming Miyembro,

Ito ay bilang kumpirmasyon na natanggap na ng ating Kooperatiba ang iyong concern. Layunin naming matugunan ito nang maayos at patas.

Narito ang mga detalye ng iyong request:

Buod ng Concern: {$summary}

Klasipikasyon: {$checkSimple} Simple   {$checkComplex} Complex

Timeline (SLA): Asahan ang ating kasagutan o update sa loob ng {$slaDays} araw.

Proseso ng Pagresolba:
• Pagsusuri ng Consumer Assistance Team (CAT).
• Imbestigasyon at pag-validate sa iyong transaksyon.
• Pormal na pagbibigay ng resolusyon.

Para sa anumang katanungan o follow-up, maaari kang makipag-ugnayan sa:
Contact Person: {$contactPerson}
Telepono/Email: {$contactInfo}

Maraming salamat sa iyong pagtitiwala sa ating kooperatiba.

Lubos na gumagalang,
{$staffName}
TEXT;
}

/**
 * Called right after a new case is inserted from the Sheet.
 * Populates the case's draft fields, creates draft version 1, and
 * logs the auto-generation in the audit trail.
 */
function generateInitialDraft(mysqli $conn, int $caseId, array $case): void {
    $fields = defaultEditableFields();
    $body   = renderCaseEmailBody($case, $fields);
    $subject = "Acknowledgement of Your Request — {$case['reference_number']}";
    $fieldsJson = json_encode($fields, JSON_UNESCAPED_UNICODE);

    // Automated action, not a logged-in admin — 0 marks it as system-generated.
    $systemUserId = 0;
    $systemRole   = 0;

    $conn->begin_transaction();
    try {
        $upd = $conn->prepare(
            "UPDATE helpdesk_cases
             SET email_subject = ?, email_body_draft = ?, status = 'draft_generated'
             WHERE id = ?"
        );
        $upd->bind_param("ssi", $subject, $body, $caseId);
        $upd->execute();

        $ins = $conn->prepare(
            "INSERT INTO helpdesk_draft_versions (case_id, version_number, editable_fields, full_body_snapshot, saved_by_user_id, saved_by_role, created_at)
             VALUES (?, 1, ?, ?, ?, ?, NOW())"
        );
        $ins->bind_param("issii", $caseId, $fieldsJson, $body, $systemUserId, $systemRole);
        $ins->execute();

        $log = $conn->prepare(
            "INSERT INTO helpdesk_audit_log (case_id, action, user_id, user_role, detail, created_at)
             VALUES (?, 'draft_created', ?, ?, 'Auto-generated on import', NOW())"
        );
        $log->bind_param("iii", $caseId, $systemUserId, $systemRole);
        $log->execute();

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("generateInitialDraft failed for case {$caseId}: " . $e->getMessage());
    }
}
