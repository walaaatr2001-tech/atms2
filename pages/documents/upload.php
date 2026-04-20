<?php
// pages/documents/upload.php
require_once '../../includes/auth.php';
// Include functions separately — we skip header.php to avoid the admin sidebar
if (file_exists('../../includes/functions.php')) {
    require_once '../../includes/functions.php';
}
// ⚠️  DO NOT include header.php here — this page is standalone like login.php
// require_once '../../includes/header.php';

$pageTitle = 'Téléverser un document';
$user = getUser();
$dept_id = $user['department_id'] ?? null;
$error   = '';
$success = '';

// Get departments
$departments = [];
$result = $conn->query("SELECT * FROM departments");
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

// Get categories
$categories = [];
$result = $conn->query("SELECT * FROM document_categories");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $category_id   = $_POST['category_id'] ?? '';
    $department_id = $_POST['department_id'] ?? $dept_id;

    if (empty($title)) {
        $error = 'Le titre est obligatoire';
    } elseif (empty($department_id)) {
        $error = 'Le département est obligatoire';
    } elseif (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $file_name   = $_FILES['file']['name'];
        $file_ext    = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'docx', 'xlsx', 'jpg', 'jpeg', 'png', 'zip', 'rar'];

        if (!in_array($file_ext, $allowed_ext)) {
            $error = 'Type de fichier non autorisé';
        } else {
            $file_size = $_FILES['file']['size'];
            if ($file_size > 52428800) {
                $error = 'Fichier trop volumineux (max 50 MB)';
            } else {
                $ref = generateRefNumber();

                $upload_dir = '../../uploads/' . date('Y') . '/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $new_file_name = $ref . '.' . $file_ext;
                $file_path     = $upload_dir . $new_file_name;

                if (move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
                    $title_esc  = $conn->real_escape_string($title);
                    $desc_esc   = $conn->real_escape_string($description);
                    $cat_id     = !empty($category_id) ? (int)$category_id : 'NULL';
                    $dept_id_int = (int)$department_id;
                    $user_id    = $user['id'];

                    $sql = "INSERT INTO documents
                                (title, description, file_path, file_type, file_size,
                                 reference_number, category_id, department_id, uploaded_by, status)
                            VALUES
                                ('$title_esc','$desc_esc','$file_path','$file_ext',
                                 $file_size,'$ref',$cat_id,$dept_id_int,$user_id,'submitted')";

                    if ($conn->query($sql)) {
                        $new_doc_id = $conn->insert_id;
                        logAction('upload_document', 'document', $new_doc_id);

                        $success = 'Document téléversé avec succès! Référence : ' . $ref;

                        // ── AI processing ──────────────────────────────────────────
                        if (isset($_POST['process_ai']) && $_POST['process_ai'] == 1) {
                            require_once '../../includes/ai_helper.php';
                            $extracted = callGeminiAI($file_path);
                            $json      = $conn->real_escape_string(
                                json_encode($extracted, JSON_UNESCAPED_UNICODE)
                            );
                            $conn->query("UPDATE documents
                                          SET extracted_json = '$json', ai_processed = 1
                                          WHERE id = $new_doc_id");
                            $success .= ' + Analyse IA terminée automatiquement !';
                        }
                        // ───────────────────────────────────────────────────────────
                    } else {
                        $error = 'Erreur : ' . $conn->error;
                    }
                } else {
                    $error = 'Erreur lors du téléchargement';
                }
            }
        }
    } else {
        $error = 'Veuillez sélectionner un fichier';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Téléverser un document — AT-AMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Same fonts as login.php -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* ── Identical base to login.php ──────────────────────────────── */
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at center, #0a1f1c 0%, #020808 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(13, 13, 13, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .input-dark {
            background: #121212 !important;
            border: 1px solid #2a2a2a !important;
            color: white !important;
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        .input-dark::placeholder { color: #3a3a3a; }
        .input-dark:focus {
            border-color: #00BFA5 !important;
            box-shadow: 0 0 0 2px rgba(0,191,165,0.15);
        }
        select.input-dark option { background: #121212; color: white; }

        .glow-line {
            height: 1px;
            width: 80px;
            background: linear-gradient(90deg, transparent, #00BFA5, transparent);
        }
        @media (min-width: 768px) { .glow-line { width: 120px; } }

        /* ── Drop zone ────────────────────────────────────────────────── */
        .drop-zone {
            background: #0d0d0d;
            border: 2px dashed #2a2a2a;
            transition: border-color .25s, background .25s;
            cursor: pointer;
        }
        .drop-zone:hover,
        .drop-zone.dragover  { border-color: #00BFA5; background: rgba(0,191,165,0.06); }
        .drop-zone.has-file  { border-color: #00BFA5; border-style: solid; background: rgba(0,191,165,0.05); }

        /* ── File pill ────────────────────────────────────────────────── */
        .file-pill {
            background: rgba(0,191,165,0.08);
            border: 1px solid rgba(0,191,165,0.2);
            border-radius: 12px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        .file-pill .ext-badge {
            background: #00BFA5;
            color: black;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 5px;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        /* ── Buttons ──────────────────────────────────────────────────── */
        .btn-primary {
            background: #00BFA5;
            color: black;
            font-weight: 800;
            letter-spacing: .05em;
            transition: background .2s, transform .15s, box-shadow .2s;
        }
        .btn-primary:hover  { background: #00e6c4; transform: scale(1.01); box-shadow: 0 0 20px rgba(0,191,165,.2); }
        .btn-primary:active { transform: scale(.99); }
        .btn-primary:disabled { opacity: .5; cursor: not-allowed; transform: none; }

        .btn-ghost {
            border: 1px solid #2a2a2a;
            color: #777;
            transition: border-color .2s, color .2s;
        }
        .btn-ghost:hover { border-color: #555; color: #ccc; }

        /* ── Field label ──────────────────────────────────────────────── */
        .field-label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 8px;
            margin-left: 4px;
        }

        /* ── AI toggle ────────────────────────────────────────────────── */
        .ai-toggle {
            background: #0d0d0d;
            border: 1px solid #1e1e1e;
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: border-color .2s, background .2s;
        }
        .ai-toggle:hover { border-color: rgba(0,191,165,.25); background: rgba(0,191,165,.04); }
        .ai-toggle input[type="checkbox"] {
            width: 18px; height: 18px;
            accent-color: #00BFA5;
            flex-shrink: 0;
            cursor: pointer;
        }

        /* ── Progress bar ─────────────────────────────────────────────── */
        #upload-progress { display:none; height:3px; background:#1a1a1a; border-radius:2px; overflow:hidden; margin-top:8px; }
        #upload-bar      { height:100%; width:0%; background:linear-gradient(90deg,#00BFA5,#00e6c4); transition:width .3s; border-radius:2px; }

        /* ── Scrollbar ────────────────────────────────────────────────── */
        ::-webkit-scrollbar       { width: 5px; }
        ::-webkit-scrollbar-track { background: #0a0a0a; }
        ::-webkit-scrollbar-thumb { background: #1e1e1e; border-radius: 4px; }
    </style>
</head>
<body class="flex flex-col items-center justify-start px-4 py-8 md:py-12 text-white">

    <!-- ── Logo (same markup as login.php) ─────────────────────────────── -->
    <div class="w-full max-w-2xl flex justify-start mb-12" data-aos="fade-down">
        <a href="list.php" class="flex items-center space-x-3 group">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-teal-800 flex items-center justify-center shadow-lg border border-teal-300/20">
                <i class="fa-solid fa-box-archive text-black text-lg"></i>
            </div>
            <div>
                <span class="block font-bold tracking-tight text-white leading-none">AT-AMS</span>
                <span class="text-[10px] text-teal-500 uppercase tracking-widest font-semibold">Digital Archives</span>
            </div>
        </a>
    </div>

    <!-- ── Heading (same style as login hero) ─────────────────────────── -->
    <div class="w-full max-w-2xl text-center mb-8 md:mb-10" data-aos="zoom-in" data-aos-delay="200">
        <h1 class="text-3xl md:text-5xl font-bold mb-3 tracking-tight">
            <span class="text-white">Archive with </span>
            <span class="text-teal-400">Precision</span>
        </h1>
        <h2 class="text-lg md:text-xl font-light text-gray-400">
            Téléversez et indexez vos fichiers dans le système
        </h2>
        <div class="flex items-center justify-center space-x-4 mt-6 opacity-50">
            <div class="glow-line"></div>
            <i class="fa-solid fa-upload text-teal-500 text-xs"></i>
            <div class="glow-line"></div>
        </div>
    </div>

    <!-- ── Card ────────────────────────────────────────────────────────── -->
    <div class="w-full max-w-2xl glass-card rounded-[2rem] md:rounded-[2.5rem] p-6 md:p-10 shadow-2xl mb-12"
         data-aos="fade-up" data-aos-delay="400">

        <div class="text-center mb-8">
            <h3 class="text-xl md:text-2xl font-semibold mb-2">Nouveau dépôt</h3>
            <p class="text-gray-500 text-sm">Remplissez les informations ci-dessous</p>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
        <div class="mb-6 bg-red-900/20 border border-red-500/40 text-red-200 px-4 py-3 rounded-xl text-sm text-center">
            <i class="fa-solid fa-triangle-exclamation mr-2"></i><?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div id="success-msg" class="mb-6 bg-teal-900/20 border border-teal-500/40 text-teal-200 px-4 py-3 rounded-xl text-sm text-center">
            <i class="fa-solid fa-circle-check mr-2"></i><?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-5 md:space-y-6" id="upload-form">

            <!-- Title -->
            <div>
                <label class="field-label">Titre du document <span class="text-teal-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-600">
                        <i class="fa-regular fa-file-lines"></i>
                    </span>
                    <input type="text" name="title" required
                           placeholder="Ex : Contrat de maintenance 2025"
                           class="input-dark w-full pl-12 pr-5 py-3.5 md:py-4 rounded-2xl">
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="field-label">Description</label>
                <textarea name="description" rows="3"
                          placeholder="Décrivez brièvement le contenu du document..."
                          class="input-dark w-full px-5 py-3.5 rounded-2xl resize-none"></textarea>
            </div>

            <!-- Dept + Category -->
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="field-label">Département <span class="text-teal-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-600 pointer-events-none">
                            <i class="fa-regular fa-building"></i>
                        </span>
                        <select name="department_id" required
                                class="input-dark w-full pl-12 pr-10 py-3.5 md:py-4 rounded-2xl appearance-none">
                            <option value="">Sélectionner</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>"
                                <?= ($dept_id == $dept['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 text-xs pointer-events-none">
                            <i class="fa-solid fa-chevron-down"></i>
                        </span>
                    </div>
                </div>

                <div>
                    <label class="field-label">Catégorie</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-600 pointer-events-none">
                            <i class="fa-regular fa-folder"></i>
                        </span>
                        <select name="category_id"
                                class="input-dark w-full pl-12 pr-10 py-3.5 md:py-4 rounded-2xl appearance-none">
                            <option value="">Sélectionner</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 text-xs pointer-events-none">
                            <i class="fa-solid fa-chevron-down"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- File drop zone -->
            <div>
                <label class="field-label">Fichier <span class="text-teal-500">*</span></label>

                <input type="file" name="file" id="file_input"
                       accept=".pdf,.docx,.xlsx,.jpg,.jpeg,.png,.zip,.rar"
                       required class="hidden">

                <div class="drop-zone rounded-2xl p-8 text-center"
                     id="drop-zone" role="button" tabindex="0"
                     aria-label="Zone de dépôt de fichier">
                    <div id="drop-idle">
                        <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-[#111] border border-[#222] flex items-center justify-center">
                            <i class="fa-solid fa-cloud-arrow-up text-teal-500 text-2xl"></i>
                        </div>
                        <p class="text-gray-400 text-sm font-medium">Cliquez ou déposez votre fichier ici</p>
                        <p class="text-gray-600 text-xs mt-2">PDF · DOCX · XLSX · JPEG · PNG · ZIP · RAR — max 50 MB</p>
                    </div>
                    <div id="drop-selected" class="hidden">
                        <i class="fa-solid fa-circle-check text-teal-400 text-3xl mb-3"></i>
                        <p class="text-teal-300 text-sm font-semibold" id="drop-filename">—</p>
                        <p class="text-gray-500 text-xs mt-1" id="drop-filesize">—</p>
                    </div>
                </div>

                <div id="upload-progress"><div id="upload-bar"></div></div>
                <div id="file_preview"></div>
            </div>

            <!-- AI toggle -->
            <label class="ai-toggle" for="process_ai">
                <input type="checkbox" name="process_ai" id="process_ai" value="1">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-white">Analyse IA automatique</p>
                    <p class="text-xs text-gray-600 mt-0.5">Extraction intelligente du contenu via Gemini AI</p>
                </div>
                <span class="text-teal-500 text-lg">✦</span>
            </label>

            <!-- Divider -->
            <div class="border-t border-white/5"></div>

            <!-- Actions -->
            <div class="flex gap-3">
                <a href="list.php"
                   class="btn-ghost px-6 py-3.5 md:py-4 rounded-2xl text-sm flex-shrink-0 flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left text-xs"></i> Annuler
                </a>
                <button type="submit" id="submit-btn"
                        class="btn-primary flex-1 py-3.5 md:py-4 rounded-2xl text-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-upload" id="btn-icon"></i>
                    <span id="btn-text">TÉLÉVERSER LE DOCUMENT</span>
                </button>
            </div>

        </form>
    </div>

    <!-- Footer (same as login.php) -->
    <footer class="mt-auto py-8 flex flex-col items-center space-y-4 w-full" data-aos="fade-up">
        <div class="flex flex-wrap justify-center gap-6 text-gray-600 text-[10px] uppercase tracking-widest font-medium">
            <span class="hover:text-teal-500 cursor-pointer">Privacy Protocol</span>
            <span class="hover:text-teal-500 cursor-pointer">Security Audit</span>
            <span class="hover:text-teal-500 cursor-pointer">Support</span>
        </div>
        <p class="text-gray-700 text-[10px] tracking-widest uppercase text-center px-4">
            &copy; <?= date('Y') ?> Algérie Télécom — Archive Management System
        </p>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 1000, once: true });

        // Auto-hide success
        const successMsg = document.getElementById('success-msg');
        if (successMsg) {
            setTimeout(() => {
                successMsg.style.transition = 'opacity 0.5s';
                successMsg.style.opacity = '0';
                setTimeout(() => successMsg.remove(), 500);
            }, 4000);
        }

        // File input wiring
        const fileInput = document.getElementById('file_input');
        const dropZone  = document.getElementById('drop-zone');
        const dropIdle  = document.getElementById('drop-idle');
        const dropSel   = document.getElementById('drop-selected');
        const dropName  = document.getElementById('drop-filename');
        const dropSize  = document.getElementById('drop-filesize');
        const preview   = document.getElementById('file_preview');

        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') fileInput.click();
        });
        dropZone.addEventListener('dragover', e => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileChange(e.dataTransfer.files[0]);
            }
        });
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) handleFileChange(fileInput.files[0]);
        });

        function handleFileChange(file) {
            const ext  = file.name.split('.').pop().toUpperCase();
            const size = formatBytes(file.size);
            dropIdle.classList.add('hidden');
            dropSel.classList.remove('hidden');
            dropName.textContent = file.name;
            dropSize.textContent = size;
            dropZone.classList.add('has-file');
            preview.innerHTML = `
                <div class="file-pill">
                    <span class="ext-badge">${ext}</span>
                    <span class="text-gray-300 text-sm font-medium truncate">${escHtml(file.name)}</span>
                    <span class="text-gray-500 text-xs ml-auto flex-shrink-0">${size}</span>
                    <button type="button" onclick="clearFile()"
                            class="text-gray-700 hover:text-red-400 text-xs ml-1 transition-colors flex-shrink-0"
                            title="Retirer">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>`;
        }

        function clearFile() {
            fileInput.value = '';
            preview.innerHTML = '';
            dropIdle.classList.remove('hidden');
            dropSel.classList.add('hidden');
            dropZone.classList.remove('has-file');
        }

        function formatBytes(b) {
            if (b < 1024)    return b + ' o';
            if (b < 1048576) return (b / 1024).toFixed(1) + ' Ko';
            return (b / 1048576).toFixed(2) + ' Mo';
        }

        function escHtml(s) {
            return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        // Submit: loading state + fake progress
        document.getElementById('upload-form').addEventListener('submit', () => {
            const btn  = document.getElementById('submit-btn');
            const icon = document.getElementById('btn-icon');
            const txt  = document.getElementById('btn-text');
            btn.disabled = true;
            icon.className = 'fa-solid fa-spinner fa-spin';
            txt.textContent = 'TÉLÉVERSEMENT EN COURS…';

            const prog = document.getElementById('upload-progress');
            const bar  = document.getElementById('upload-bar');
            prog.style.display = 'block';
            let w = 0;
            setInterval(() => {
                w = Math.min(w + Math.random() * 8, 90);
                bar.style.width = w + '%';
            }, 200);
        });
    </script>
</body>
</html>
<?php // ⚠️  DO NOT include footer.php — standalone page like login.php ?>