<?php
require_once 'config.php';
requireResident();
$pageTitle = 'Request Document';
$basePath  = '';
$userId    = (int)$_SESSION['user_id'];
$user      = getCurrentUser();
$success   = getFlash('success');
$error     = getFlash('error');

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $docType = $_POST['document_type'] ?? '';
    $allowed = ['barangay_clearance','certificate_of_indigency','business_clearance','barangay_blotter'];
    if (!in_array($docType, $allowed)) {
        setFlash('error', 'Invalid document type.');
        redirect('request_documents.php');
    }

    $data = [
        'user_id'           => $userId,
        'document_type'     => $docType,
        'status'            => 'Pending',
        'purpose'           => trim($_POST['purpose'] ?? ''),
        'full_name'         => trim($_POST['full_name'] ?? $user['full_name']),
        'complete_address'  => trim($_POST['complete_address'] ?? $user['address']),
        'date_of_birth'     => $_POST['date_of_birth'] ?? $user['date_of_birth'],
        'place_of_birth'    => trim($_POST['place_of_birth'] ?? $user['place_of_birth']),
        'civil_status'      => $_POST['civil_status'] ?? $user['civil_status'],
        'citizenship'       => trim($_POST['citizenship'] ?? $user['citizenship']),
        'period_of_residency' => trim($_POST['period_of_residency'] ?? ''),
        'cedula_number'     => trim($_POST['cedula_number'] ?? $user['cedula_number']),
        // Indigency
        'monthly_income'    => !empty($_POST['monthly_income']) ? (float)$_POST['monthly_income'] : null,
        'annual_income'     => !empty($_POST['annual_income'])  ? (float)$_POST['annual_income']  : null,
        'target_institution'=> trim($_POST['target_institution'] ?? ''),
        'specific_benefit'  => trim($_POST['specific_benefit'] ?? ''),
        // Business
        'business_name'     => trim($_POST['business_name'] ?? ''),
        'business_address'  => trim($_POST['business_address'] ?? ''),
        'type_of_ownership' => $_POST['type_of_ownership'] ?? null,
        'nature_of_business'=> trim($_POST['nature_of_business'] ?? ''),
        'capital_investment' => !empty($_POST['capital_investment']) ? (float)$_POST['capital_investment'] : null,
        // Blotter
        'complainant_name'  => trim($_POST['complainant_name'] ?? ''),
        'complainant_address'=> trim($_POST['complainant_address'] ?? ''),
        'complainant_contact'=> trim($_POST['complainant_contact'] ?? ''),
        'respondent_name'   => trim($_POST['respondent_name'] ?? ''),
        'respondent_address'=> trim($_POST['respondent_address'] ?? ''),
        'respondent_contact'=> trim($_POST['respondent_contact'] ?? ''),
        'case_type'         => trim($_POST['case_type'] ?? ''),
        'date_of_occurrence'=> !empty($_POST['date_of_occurrence']) ? $_POST['date_of_occurrence'] : null,
        'place_of_incident' => trim($_POST['place_of_incident'] ?? ''),
        'narrative_of_events'=> trim($_POST['narrative_of_events'] ?? ''),
        'witnesses'         => trim($_POST['witnesses'] ?? ''),
        'evidence_description'=> trim($_POST['evidence_description'] ?? ''),
        'desired_action'    => trim($_POST['desired_action'] ?? ''),
    ];

    $dob = !empty($data['date_of_birth']) ? $data['date_of_birth'] : null;
    $st = $conn->prepare("INSERT INTO document_requests (user_id,document_type,status,purpose,full_name,complete_address,date_of_birth,place_of_birth,civil_status,citizenship,period_of_residency,cedula_number,monthly_income,annual_income,target_institution,specific_benefit,business_name,business_address,type_of_ownership,nature_of_business,capital_investment,complainant_name,complainant_address,complainant_contact,respondent_name,respondent_address,respondent_contact,case_type,date_of_occurrence,place_of_incident,narrative_of_events,witnesses,evidence_description,desired_action) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $st->bind_param('isssssssssssddssssssdsssssssssssss',
        $data['user_id'], $data['document_type'], $data['status'], $data['purpose'],
        $data['full_name'], $data['complete_address'], $dob, $data['place_of_birth'],
        $data['civil_status'], $data['citizenship'], $data['period_of_residency'], $data['cedula_number'],
        $data['monthly_income'], $data['annual_income'], $data['target_institution'], $data['specific_benefit'],
        $data['business_name'], $data['business_address'], $data['type_of_ownership'], $data['nature_of_business'],
        $data['capital_investment'],
        $data['complainant_name'], $data['complainant_address'], $data['complainant_contact'],
        $data['respondent_name'], $data['respondent_address'], $data['respondent_contact'],
        $data['case_type'], $data['date_of_occurrence'], $data['place_of_incident'],
        $data['narrative_of_events'], $data['witnesses'], $data['evidence_description'], $data['desired_action']
    );

    if ($st->execute()) {
        $newId = $conn->insert_id;
        $docLabel = documentTypeLabel($docType);
        createNotification($userId, 'Request Submitted', "Your $docLabel request (#$newId) has been submitted and is now pending review.", 'request', $newId);
        notifyAllAdmins('New Document Request', $user['full_name'] . " submitted a $docLabel request.", 'request', $newId);
        setFlash('success', 'Your request has been submitted successfully! We will notify you once it is reviewed.');
        redirect('view_request.php');
    } else {
        setFlash('error', 'Failed to submit request. Please try again.');
        redirect('request_documents.php');
    }
}

$preType = $_GET['type'] ?? '';
include_once 'includes/layout.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Request a Document</h1>
        <p class="page-desc">Select the document you need and fill in the required information.</p>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible"><?= e($success) ?><button class="alert-close">&times;</button></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error alert-dismissible"><?= e($error) ?><button class="alert-close">&times;</button></div>
<?php endif; ?>

<!-- STEP INDICATOR -->
<div class="steps-indicator" id="stepsIndicator">
    <div class="step-ind active" id="step1ind"><span class="step-num">1</span><span class="step-lbl">Choose Document</span></div>
    <div class="step-ind" id="step2ind"><span class="step-num">2</span><span class="step-lbl">Fill Details</span></div>
    <div class="step-ind" id="step3ind"><span class="step-num">3</span><span class="step-lbl">Submit</span></div>
</div>

<!-- STEP 1: CHOOSE TYPE -->
<div id="step1" class="request-step">
    <div class="doc-type-grid">
        <?php
        $docTypes = [
            ['barangay_clearance',       '📋', 'Barangay Clearance',        'Good moral character certification for employment, travel, and other legal purposes.'],
            ['certificate_of_indigency', '🤝', 'Certificate of Indigency',  'For DSWD, PhilHealth, PAO, scholarship, burial assistance, and medical benefit applications.'],
            ['business_clearance',       '🏪', 'Business Clearance',        'Clearance for new or renewing business establishments operating in the barangay.'],
            ['barangay_blotter',         '⚖️', 'Barangay Blotter',          'Official recording of incidents, complaints, and disputes for resolution.'],
        ];
        foreach ($docTypes as [$type, $icon, $label, $desc]): ?>
        <div class="doc-type-card <?= $preType === $type ? 'selected' : '' ?>" data-type="<?= $type ?>">
            <div class="doc-type-icon"><?= $icon ?></div>
            <h3><?= $label ?></h3>
            <p><?= $desc ?></p>
            <button class="btn btn-primary btn-sm select-doc-btn" data-type="<?= $type ?>">Select →</button>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- STEP 2: FORM -->
<div id="step2" class="request-step" style="display:none">
    <form method="POST" action="request_documents.php" id="requestForm">
        <input type="hidden" name="document_type" id="docTypeInput">

        <!-- COMMON PERSONAL INFO -->
        <div class="card" style="margin-bottom:1.5rem">
            <div class="card-header"><h3 id="formTitle">Personal Information</h3></div>
            <div class="card-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="req">*</span></label>
                        <input type="text" name="full_name" class="form-input" value="<?= e($user['full_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-input" value="<?= e($user['date_of_birth']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Place of Birth</label>
                        <input type="text" name="place_of_birth" class="form-input" value="<?= e($user['place_of_birth']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Civil Status</label>
                        <select name="civil_status" class="form-input form-select">
                            <?php foreach (['Single','Married','Widowed','Separated','Annulled'] as $cs): ?>
                            <option value="<?= $cs ?>" <?= $user['civil_status']===$cs?'selected':'' ?>><?= $cs ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Citizenship</label>
                        <input type="text" name="citizenship" class="form-input" value="<?= e($user['citizenship'] ?: 'Filipino') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cedula Number</label>
                        <input type="text" name="cedula_number" class="form-input" value="<?= e($user['cedula_number']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Period of Residency</label>
                        <input type="text" name="period_of_residency" class="form-input" placeholder="e.g. 5 years">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Complete Address <span class="req">*</span></label>
                    <textarea name="complete_address" class="form-input form-textarea" rows="2" required><?= e($user['address']) ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Purpose <span class="req">*</span></label>
                    <input type="text" name="purpose" class="form-input" required placeholder="State the purpose of this request…">
                </div>
            </div>
        </div>

        <!-- INDIGENCY FIELDS -->
        <div class="card doc-extra-fields" id="fields_indigency" style="display:none;margin-bottom:1.5rem">
            <div class="card-header"><h3>Indigency Details</h3></div>
            <div class="card-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Monthly Income (₱)</label>
                        <input type="number" name="monthly_income" class="form-input" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Annual Income (₱)</label>
                        <input type="number" name="annual_income" class="form-input" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Target Institution</label>
                        <select name="target_institution" class="form-input form-select">
                            <option value="">Select institution…</option>
                            <?php foreach (['DSWD','PhilHealth','PAO','DOLE','DepEd','DOH','CHED','Others'] as $inst): ?>
                            <option value="<?= $inst ?>"><?= $inst ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Specific Benefit</label>
                        <select name="specific_benefit" class="form-input form-select">
                            <option value="">Select benefit…</option>
                            <?php foreach (['Burial Assistance','Scholarship','Medical Discount','Cash Assistance','Food Assistance','Housing Assistance','Legal Aid','Others'] as $ben): ?>
                            <option value="<?= $ben ?>"><?= $ben ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- BUSINESS CLEARANCE FIELDS -->
        <div class="card doc-extra-fields" id="fields_business" style="display:none;margin-bottom:1.5rem">
            <div class="card-header"><h3>Business Information</h3></div>
            <div class="card-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Business Name <span class="req">*</span></label>
                        <input type="text" name="business_name" class="form-input" placeholder="Registered business name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nature of Business</label>
                        <input type="text" name="nature_of_business" class="form-input" placeholder="e.g. Retail, Restaurant, Services">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type of Ownership</label>
                        <select name="type_of_ownership" class="form-input form-select">
                            <option value="Sole Proprietorship">Sole Proprietorship</option>
                            <option value="Partnership">Partnership</option>
                            <option value="Corporation">Corporation</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Capital Investment (₱)</label>
                        <input type="number" name="capital_investment" class="form-input" min="0" step="0.01" placeholder="0.00">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Business Address</label>
                    <textarea name="business_address" class="form-input form-textarea" rows="2" placeholder="Complete business address"></textarea>
                </div>
            </div>
        </div>

        <!-- BLOTTER FIELDS -->
        <div class="card doc-extra-fields" id="fields_blotter" style="display:none;margin-bottom:1.5rem">
            <div class="card-header"><h3>Blotter / Incident Report</h3></div>
            <div class="card-body">
                <div class="form-section-label">Complainant Information</div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Complainant Name</label>
                        <input type="text" name="complainant_name" class="form-input" value="<?= e($user['full_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Complainant Contact</label>
                        <input type="text" name="complainant_contact" class="form-input" value="<?= e($user['phone']) ?>">
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Complainant Address</label>
                        <input type="text" name="complainant_address" class="form-input" value="<?= e($user['address']) ?>">
                    </div>
                </div>
                <div class="form-section-label">Respondent Information</div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Respondent Name</label>
                        <input type="text" name="respondent_name" class="form-input" placeholder="Name of the respondent">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Respondent Contact</label>
                        <input type="text" name="respondent_contact" class="form-input" placeholder="Contact number">
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Respondent Address</label>
                        <input type="text" name="respondent_address" class="form-input" placeholder="Address of respondent">
                    </div>
                </div>
                <div class="form-section-label">Incident Details</div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Case Type</label>
                        <select name="case_type" class="form-input form-select">
                            <option value="">Select case type…</option>
                            <?php foreach (['Theft','Physical Injury','Oral Defamation','Estafa/Fraud','Trespassing','Noise Complaint','Domestic Dispute','Property Damage','Harassment','Others'] as $ct): ?>
                            <option value="<?= $ct ?>"><?= $ct ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date & Time of Occurrence</label>
                        <input type="datetime-local" name="date_of_occurrence" class="form-input">
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Place of Incident</label>
                        <input type="text" name="place_of_incident" class="form-input" placeholder="Exact location of the incident">
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Narrative of Events</label>
                        <textarea name="narrative_of_events" class="form-input form-textarea" rows="5" placeholder="Describe what happened in detail…"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Witnesses</label>
                        <textarea name="witnesses" class="form-input form-textarea" rows="3" placeholder="Name and contact info of witnesses…"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Evidence Description</label>
                        <textarea name="evidence_description" class="form-input form-textarea" rows="3" placeholder="Describe any physical evidence…"></textarea>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Desired Action</label>
                        <textarea name="desired_action" class="form-input form-textarea" rows="3" placeholder="What resolution or action are you requesting?"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions" style="display:flex;gap:1rem">
            <button type="button" class="btn btn-ghost" id="backBtn">← Back</button>
            <button type="submit" class="btn btn-primary btn-lg">Submit Request →</button>
        </div>
    </form>
</div>

<script>
(function(){
    const step1    = document.getElementById('step1');
    const step2    = document.getElementById('step2');
    const s1ind    = document.getElementById('step1ind');
    const s2ind    = document.getElementById('step2ind');
    const docInput = document.getElementById('docTypeInput');
    const formTitle= document.getElementById('formTitle');
    const backBtn  = document.getElementById('backBtn');

    const labels = {
        barangay_clearance:       'Barangay Clearance',
        certificate_of_indigency: 'Certificate of Indigency',
        business_clearance:       'Business Clearance',
        barangay_blotter:         'Barangay Blotter',
    };

    function showStep2(type) {
        docInput.value = type;
        formTitle.textContent = labels[type] + ' — Details';
        // Hide extra fields
        document.querySelectorAll('.doc-extra-fields').forEach(el => el.style.display = 'none');
        if (type === 'certificate_of_indigency') document.getElementById('fields_indigency').style.display = '';
        if (type === 'business_clearance')       document.getElementById('fields_business').style.display = '';
        if (type === 'barangay_blotter')         document.getElementById('fields_blotter').style.display  = '';
        step1.style.display = 'none';
        step2.style.display = '';
        s1ind.classList.remove('active'); s1ind.classList.add('done');
        s2ind.classList.add('active');
        window.scrollTo({top:0, behavior:'smooth'});
    }

    document.querySelectorAll('.select-doc-btn').forEach(btn => {
        btn.addEventListener('click', () => showStep2(btn.dataset.type));
    });
    document.querySelectorAll('.doc-type-card').forEach(card => {
        card.addEventListener('dblclick', () => showStep2(card.dataset.type));
    });
    backBtn.addEventListener('click', () => {
        step2.style.display = 'none';
        step1.style.display = '';
        s1ind.classList.add('active'); s1ind.classList.remove('done');
        s2ind.classList.remove('active');
    });

    // Pre-select type from URL
    const preType = '<?= e($preType) ?>';
    if (preType && labels[preType]) showStep2(preType);
})();
</script>

<?php include_once 'includes/footer.php'; ?>