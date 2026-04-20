<?php require_once '../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AT-AMS - Inscription</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at center, #0a2d2d 0%, #050f0f 100%);
            color: white;
            overflow-x: hidden;
        }
        .glow-bg {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 50% -20%, rgba(20, 184, 166, 0.15) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
        .glass-card {
            background: rgba(13, 13, 13, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .input-field {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .input-field:focus {
            border-color: #14b8a6;
            background: rgba(255, 255, 255, 0.08);
            outline: none;
            box-shadow: 0 0 15px rgba(20, 184, 166, 0.25);
            transform: translateY(-1px);
        }
        .input-field.error {
            border-color: #ef4444;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.2);
        }
        .error-msg {
            color: #ef4444;
            font-size: 10px;
            margin-top: 4px;
            margin-left: 4px;
            display: none;
        }
        .btn-primary {
            background: #14b8a6;
            color: #050f0f;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-primary:hover {
            background: #0ea5e9;
            box-shadow: 0 0 20px rgba(20, 184, 166, 0.4);
            transform: translateY(-1px);
        }
        .step { display: none; }
        .step.active { display: block; animation: slideUp 0.4s ease-out forwards; }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Redirect overlay animation */
        #redirect-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(5, 15, 15, 0.95);
            z-index: 9999;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            animation: fadeInOverlay 0.5s ease forwards;
        }
        #redirect-overlay.show { display: flex; }
        @keyframes fadeInOverlay {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        .spinner {
            width: 56px; height: 56px;
            border: 3px solid rgba(20,184,166,0.2);
            border-top-color: #14b8a6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        select option { background: #0a2d2d; color: white; }

        /* RIB field highlight */
        .rib-hint {
            font-size: 10px;
            color: #64748b;
            margin-top: 4px;
            margin-left: 4px;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4 md:p-8 relative">

    <!-- Animated redirect overlay -->
    <div id="redirect-overlay">
        <div class="spinner mb-6"></div>
        <p class="text-teal-400 text-sm uppercase tracking-widest font-semibold">Inscription réussie</p>
        <p class="text-gray-500 text-xs mt-2">Redirection vers la connexion...</p>
    </div>

    <div class="glow-bg"></div>

    <div class="text-center mb-8 z-10">
        <h1 class="text-4xl md:text-5xl font-bold tracking-tight mb-2">
            Preserve with <span class="text-teal-400">Precision</span>
        </h1>
        <p class="text-gray-400 text-lg">Algérie Télécom — Archive Management System</p>
    </div>

    <!-- Error message from PHP -->
    <?php if (!empty($_GET['error'])): ?>
    <div class="w-full max-w-2xl mb-4 z-10 bg-red-900/40 border border-red-500/40 rounded-xl px-5 py-3 text-red-400 text-sm">
        <i class="fa fa-circle-exclamation mr-2"></i>
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
    <?php endif; ?>

    <div class="w-full max-w-2xl glass-card p-6 md:p-10 z-10 relative">

        <div class="text-center mb-8">
            <h2 class="text-xl font-semibold uppercase tracking-widest text-gray-300">Portail d'Inscription</h2>
            <p class="text-gray-600 text-xs mt-1">Entreprises partenaires uniquement</p>
            <!-- Progress dots -->
            <div class="flex justify-center mt-6 space-x-2">
                <div id="p-1" class="h-1.5 w-12 bg-teal-500 rounded-full transition-all duration-300"></div>
                <div id="p-2" class="h-1.5 w-12 bg-gray-800 rounded-full transition-all duration-300"></div>
                <div id="p-3" class="h-1.5 w-12 bg-gray-800 rounded-full transition-all duration-300"></div>
                <div id="p-4" class="h-1.5 w-12 bg-gray-800 rounded-full transition-all duration-300"></div>
            </div>
            <p id="step-label" class="text-[10px] text-gray-600 mt-2 uppercase tracking-widest">Étape 1 / 4 — Informations Personnelles</p>
        </div>

        <form id="registerForm" action="register_action.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

            <!-- ======= STEP 1 — Personal Info ======= -->
            <div id="step1" class="step active">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] uppercase text-gray-500 font-bold mb-1.5 block ml-1">Prénom *</label>
                        <input type="text" name="first_name" id="first_name" required
                               class="input-field w-full px-4 py-3 rounded-xl" placeholder="Mohamed">
                        <span class="error-msg" id="err-first_name">Ce champ est obligatoire.</span>
                    </div>
                    <div>
                        <label class="text-[10px] uppercase text-gray-500 font-bold mb-1.5 block ml-1">Nom *</label>
                        <input type="text" name="last_name" id="last_name" required
                               class="input-field w-full px-4 py-3 rounded-xl" placeholder="Benali">
                        <span class="error-msg" id="err-last_name">Ce champ est obligatoire.</span>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="text-[10px] uppercase text-gray-500 font-bold mb-1.5 block ml-1">Nom d'utilisateur *</label>
                    <input type="text" name="username" id="username" required
                           class="input-field w-full px-4 py-3 rounded-xl" placeholder="m.benali">
                    <span class="error-msg" id="err-username">Ce champ est obligatoire.</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="text-[10px] uppercase text-gray-500 font-bold mb-1.5 block ml-1">Email *</label>
                        <input type="email" name="email" id="email" required
                               class="input-field w-full px-4 py-3 rounded-xl" placeholder="email@entreprise.dz">
                        <span class="error-msg" id="err-email">Email invalide.</span>
                    </div>
                    <div>
                        <label class="text-[10px] uppercase text-gray-500 font-bold mb-1.5 block ml-1">Téléphone *</label>
                        <input type="tel" name="phone" id="phone" required
                               class="input-field w-full px-4 py-3 rounded-xl" placeholder="0550XXXXXX">
                        <span class="error-msg" id="err-phone">Format: 0550XXXXXX ou +213XXXXXXXXX</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="text-[10px] uppercase text-gray-500 font-bold mb-1.5 block ml-1">Mot de passe *</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required
                                   class="input-field w-full px-4 py-3 pr-12 rounded-xl" placeholder="••••••••">
                            <button type="button" onclick="togglePwd('password','eyeIcon1')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-teal-400 transition">
                                <i id="eyeIcon1" class="fa fa-eye text-sm"></i>
                            </button>
                        </div>
                        <span class="error-msg" id="err-password">Minimum 6 caractères.</span>
                    </div>
                    <div>
                        <label class="text-[10px] uppercase text-gray-500 font-bold mb-1.5 block ml-1">Confirmer *</label>
                        <div class="relative">
                            <input type="password" name="confirm_password" id="confirm_password" required
                                   class="input-field w-full px-4 py-3 pr-12 rounded-xl" placeholder="••••••••">
                            <button type="button" onclick="togglePwd('confirm_password','eyeIcon2')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-teal-400 transition">
                                <i id="eyeIcon2" class="fa fa-eye text-sm"></i>
                            </button>
                        </div>
                        <span class="error-msg" id="err-confirm_password">Les mots de passe ne correspondent pas.</span>
                    </div>
                </div>
            </div>

            <!-- ======= STEP 2 — Company Info ======= -->
            <div id="step2" class="step">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] uppercase text-gray-500 font-bold mb-1.5 block ml-1">NIF *</label>
                        <input type="text" name="nif" id="nif" required
                               class="input-field w-full px-4 py-3 rounded-xl" placeholder="Numéro Identifiant Fiscal">
                        <span class="error-msg" id="err-nif">Ce champ est obligatoire.</span>
                    </div>
                    <div>
                        <label class="text-[10px] uppercase text-gray-500 font-bold mb-1.5 block ml-1">RC *</label>
                        <input type="text" name="rc" id="rc" required
                               class="input-field w-full px-4 py-3 rounded-xl" placeholder="Registre de Commerce">
                        <span class="error-msg" id="err-rc">Ce champ est obligatoire.</span>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="text-[10px] uppercase text-gray-500 font-bold mb-1.5 block ml-1">
                        RIB * <span class="text-gray-600 normal-case font-normal">(Relevé d'Identité Bancaire — 20 chiffres)</span>
                    </label>
                    <input type="text" name="rib" id="rib" required maxlength="20"
                           pattern="[0-9]{20}"
                           class="input-field w-full px-4 py-3 rounded-xl font-mono tracking-widest"
                           placeholder="00000000000000000000"
                           oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,20); updateRibCounter(this)">
                    <div class="flex justify-between mt-1 ml-1">
                        <span class="error-msg" id="err-rib" style="display:block;color:transparent">Le RIB doit contenir exactement 20 chiffres.</span>
                        <span id="rib-counter" class="text-[10px] text-gray-600">0 / 20</span>
                    </div>
                </div>

                <!-- Bank selection -->
                <div class="mt-4">
                    <label class="text-[10px] uppercase text-gray-500 font-bold mb-1.5 block ml-1">Banque *</label>
                    <select name="bank_name" id="bank_name" required
                            class="input-field w-full px-4 py-3 rounded-xl"
                            onchange="toggleBankOther(this.value)">
                        <option value="">Sélectionnez une banque</option>
                        <?php
                        $conn = getDB();
                        $banks = $conn->query("SELECT name FROM banks ORDER BY name");
                        while ($b = $banks->fetch_assoc()):
                            $val = htmlspecialchars($b['name']);
                        ?>
                        <option value="<?= $val ?>"><?= $val ?></option>
                        <?php endwhile; ?>
                    </select>
                    <span class="error-msg" id="err-bank_name">Veuillez sélectionner une banque.</span>
                </div>

                <!-- Other bank field (hidden by default) -->
                <div id="bank-other-wrap" class="mt-4 hidden">
                    <label class="text-[10px] uppercase text-gray-500 font-bold mb-1.5 block ml-1">Précisez votre banque *</label>
                    <input type="text" name="bank_other" id="bank_other"
                           class="input-field w-full px-4 py-3 rounded-xl" placeholder="Nom de votre banque">
                    <span class="error-msg" id="err-bank_other">Ce champ est obligatoire si vous choisissez Autre.</span>
                </div>
            </div>

            <!-- ======= STEP 3 — Location ======= -->
            <div id="step3" class="step">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] uppercase text-gray-500 font-bold mb-1.5 block ml-1">Wilaya *</label>
                        <select name="wilaya_id" id="wilaya_select" required
                                class="input-field w-full px-4 py-3 rounded-xl">
                            <option value="">Sélectionnez une wilaya</option>
                            <option value="1">01 - Adrar</option>
                            <option value="2">02 - Chlef</option>
                            <option value="3">03 - Laghouat</option>
                            <option value="4">04 - Oum El Bouaghi</option>
                            <option value="5">05 - Batna</option>
                            <option value="6">06 - Béjaïa</option>
                            <option value="7">07 - Biskra</option>
                            <option value="8">08 - Béchar</option>
                            <option value="9">09 - Blida</option>
                            <option value="10">10 - Bouira</option>
                            <option value="11">11 - Tamanrasset</option>
                            <option value="12">12 - Tébessa</option>
                            <option value="13">13 - Tlemcen</option>
                            <option value="14">14 - Tiaret</option>
                            <option value="15">15 - Tizi Ouzou</option>
                            <option value="16">16 - Alger</option>
                            <option value="17">17 - Djelfa</option>
                            <option value="18">18 - Jijel</option>
                            <option value="19">19 - Sétif</option>
                            <option value="20">20 - Saïda</option>
                            <option value="21">21 - Skikda</option>
                            <option value="22">22 - Sidi Bel Abbès</option>
                            <option value="23">23 - Annaba</option>
                            <option value="24">24 - Guelma</option>
                            <option value="25">25 - Constantine</option>
                            <option value="26">26 - Médéa</option>
                            <option value="27">27 - Mostaganem</option>
                            <option value="28">28 - M'Sila</option>
                            <option value="29">29 - Mascara</option>
                            <option value="30">30 - Ouargla</option>
                            <option value="31">31 - Oran</option>
                            <option value="32">32 - El Bayadh</option>
                            <option value="33">33 - Illizi</option>
                            <option value="34">34 - Bordj Bou Arréridj</option>
                            <option value="35">35 - Boumerdès</option>
                            <option value="36">36 - El Tarf</option>
                            <option value="37">37 - Tindouf</option>
                            <option value="38">38 - Tissemsilt</option>
                            <option value="39">39 - El Oued</option>
                            <option value="40">40 - Khenchela</option>
                            <option value="41">41 - Souk Ahras</option>
                            <option value="42">42 - Tipaza</option>
                            <option value="43">43 - Mila</option>
                            <option value="44">44 - Aïn Defla</option>
                            <option value="45">45 - Naâma</option>
                            <option value="46">46 - Aïn Témouchent</option>
                            <option value="47">47 - Ghardaïa</option>
                            <option value="48">48 - Relizane</option>
                            <option value="49">49 - Timimoun</option>
                            <option value="50">50 - Bordj Badji Mokhtar</option>
                            <option value="51">51 - Ouled Djellal</option>
                            <option value="52">52 - Béni Abbès</option>
                            <option value="53">53 - In Salah</option>
                            <option value="54">54 - In Guezzam</option>
                            <option value="55">55 - Touggourt</option>
                            <option value="56">56 - Djanet</option>
                            <option value="57">57 - El M'Ghair</option>
                            <option value="58">58 - El Meniaa</option>
                        </select>
                        <span class="error-msg" id="err-wilaya_id">Veuillez sélectionner une wilaya.</span>
                    </div>
                    <div>
                        <label class="text-[10px] uppercase text-gray-500 font-bold mb-1.5 block ml-1">Commune / Ville *</label>
                        <select name="city_id" id="city_select" required
                                class="input-field w-full px-4 py-3 rounded-xl">
                            <option value="">Sélectionnez la wilaya d'abord</option>
                        </select>
                        <span class="error-msg" id="err-city_id">Veuillez sélectionner une ville.</span>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="text-[10px] uppercase text-gray-500 font-bold mb-1.5 block ml-1">Adresse exacte de l'agence *</label>
                    <textarea name="address" id="address" required rows="3"
                              class="input-field w-full px-4 py-3 rounded-xl resize-none"
                              placeholder="Rue, Bâtiment, N° Agence..."></textarea>
                    <span class="error-msg" id="err-address">Ce champ est obligatoire.</span>
                </div>
            </div>

            <!-- ======= STEP 4 — Review ======= -->
            <div id="step4" class="step">
                <div class="text-teal-400 text-xs font-bold uppercase mb-4 tracking-widest">
                    <i class="fa fa-check-circle mr-2"></i>Récapitulatif de l'inscription
                </div>
                <div id="summary"
                     class="bg-white/5 border border-white/10 rounded-2xl p-6 text-sm text-gray-300 space-y-3">
                </div>
                <p class="text-gray-600 text-xs mt-4 text-center">
                    Votre compte sera en attente de validation par un administrateur.
                </p>
            </div>

            <!-- Navigation buttons -->
            <div class="flex items-center gap-4 mt-8">
                <button type="button" onclick="prevStep()" id="prevBtn"
                        class="hidden flex-1 py-4 rounded-xl text-gray-400 hover:text-white border border-white/10 hover:border-white/30 font-semibold transition-all">
                    <i class="fa fa-arrow-left mr-2 text-sm"></i>Retour
                </button>
                <button type="button" onclick="nextStep()" id="nextBtn"
                        class="flex-[2] btn-primary py-4 rounded-xl font-bold text-sm">
                    Continuer <i class="fa fa-arrow-right ml-2 text-sm"></i>
                </button>
            </div>
        </form>

        <p class="text-center text-gray-600 text-xs mt-6">
            Déjà inscrit ?
            <a href="../auth/login.php" class="text-teal-400 hover:text-teal-300 transition ml-1">Se connecter</a>
        </p>
    </div>

    <footer class="mt-8 text-gray-600 text-[10px] uppercase tracking-widest z-10">
        Algérie Télécom &copy; 2026 — Système d'Archivage
    </footer>

    <script>
    // ======================================================
    // Cities data by wilaya ID
    // ======================================================
    // ---- Wilaya → City dynamic loading (FIXED) ----
document.getElementById('wilaya_select').addEventListener('change', function () {
    const citySelect = document.getElementById('city_select');
    citySelect.innerHTML = '<option value="">Chargement...</option>';

    const wilayaId = parseInt(this.value);
    if (!wilayaId) {
        citySelect.innerHTML = '<option value="">Sélectionnez la wilaya d\'abord</option>';
        return;
    }

    fetch('get_cities.php?wilaya_id=' + wilayaId)
        .then(res => res.json())
        .then(cities => {
            if (cities.length === 0) {
                citySelect.innerHTML = '<option value="">Aucune ville trouvée</option>';
                return;
            }
            citySelect.innerHTML = '<option value="">Sélectionnez une ville</option>';
            cities.forEach(city => {
                const opt = document.createElement('option');
                opt.value = city.id;        // ✅ integer ID — FK in DB
                opt.textContent = city.name;
                citySelect.appendChild(opt);
            });
        })
        .catch(() => {
            citySelect.innerHTML = '<option value="">Erreur de chargement</option>';
        });
});

    const stepLabels = [
        'Étape 1 / 4 — Informations Personnelles',
        'Étape 2 / 4 — Informations Entreprise',
        'Étape 3 / 4 — Localisation',
        'Étape 4 / 4 — Récapitulatif'
    ];

    let currentStep = 1;

    // ---- Wilaya → City dynamic loading ----
    document.getElementById('wilaya_select').addEventListener('change', function () {
        const citySelect = document.getElementById('city_select');
        citySelect.innerHTML = '<option value="">Sélectionnez une ville</option>';
        const cities = citiesByWilaya[parseInt(this.value)];
        if (cities) {
            cities.forEach(city => {
                const opt = document.createElement('option');
                opt.value = city;
                opt.textContent = city;
                citySelect.appendChild(opt);
            });
        }
    });

    // ---- Bank "Autre" toggle ----
    function toggleBankOther(val) {
        const wrap = document.getElementById('bank-other-wrap');
        const input = document.getElementById('bank_other');
        if (val === 'Autre') {
            wrap.classList.remove('hidden');
            input.required = true;
        } else {
            wrap.classList.add('hidden');
            input.required = false;
            input.value = '';
        }
    }

    // ---- RIB counter ----
    function updateRibCounter(input) {
        const counter = document.getElementById('rib-counter');
        const len = input.value.length;
        counter.textContent = len + ' / 20';
        counter.style.color = len === 20 ? '#14b8a6' : '#64748b';
    }

    // ---- Password toggle visibility ----
    function togglePwd(fieldId, iconId) {
        const field = document.getElementById(fieldId);
        const icon  = document.getElementById(iconId);
        if (field.type === 'password') {
            field.type = 'text';
            icon.className = 'fa fa-eye-slash text-sm';
        } else {
            field.type = 'password';
            icon.className = 'fa fa-eye text-sm';
        }
    }

    // ---- Progress bar ----
    function updateProgress(step) {
        for (let i = 1; i <= 4; i++) {
            const bar = document.getElementById('p-' + i);
            bar.classList.toggle('bg-teal-500', i <= step);
            bar.classList.toggle('bg-gray-800', i > step);
        }
        document.getElementById('step-label').textContent = stepLabels[step - 1];
    }

    // ---- Show step ----
    function showStep(n) {
        document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
        document.getElementById('step' + n).classList.add('active');
        document.getElementById('prevBtn').classList.toggle('hidden', n === 1);
        const nextBtn = document.getElementById('nextBtn');
        if (n === 4) {
            nextBtn.innerHTML = '<i class="fa fa-paper-plane mr-2 text-sm"></i>Soumettre l\'inscription';
        } else {
            nextBtn.innerHTML = 'Continuer <i class="fa fa-arrow-right ml-2 text-sm"></i>';
        }
        updateProgress(n);
        if (n === 4) generateSummary();
    }

    // ---- Field validation ----
    function validateStep(step) {
        let valid = true;

        const rules = {
            1: ['first_name','last_name','username','email','phone','password','confirm_password'],
            2: ['nif','rc','rib','bank_name'],
            3: ['wilaya_id','city_id','address']
        };

        if (!rules[step]) return true;

        rules[step].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            const err = document.getElementById('err-' + id);
            let ok = true;

            if (!el.value.trim()) {
                ok = false;
            } else if (id === 'email') {
                ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value);
            } else if (id === 'phone') {
                ok = /^(\+213|0)[0-9]{9}$/.test(el.value.trim());
            } else if (id === 'rib') {
                ok = /^[0-9]{20}$/.test(el.value.trim());
            } else if (id === 'confirm_password') {
                ok = el.value === document.getElementById('password').value;
            }

            el.classList.toggle('error', !ok);
            if (err) {
                err.style.display = ok ? 'none' : 'block';
                err.style.color   = ok ? 'transparent' : '#ef4444';
            }
            if (!ok) valid = false;
        });

        // Validate bank_other if Autre selected
        if (step === 2 && document.getElementById('bank_name').value === 'Autre') {
            const bo  = document.getElementById('bank_other');
            const err = document.getElementById('err-bank_other');
            if (!bo.value.trim()) {
                bo.classList.add('error');
                if (err) { err.style.display = 'block'; err.style.color = '#ef4444'; }
                valid = false;
            } else {
                bo.classList.remove('error');
                if (err) err.style.display = 'none';
            }
        }

        return valid;
    }

    // ---- Next / Submit ----
    function nextStep() {
        if (!validateStep(currentStep)) return;
        if (currentStep < 4) {
            currentStep++;
            showStep(currentStep);
        } else {
            // Show animated overlay then submit
            document.getElementById('redirect-overlay').classList.add('show');
            setTimeout(() => {
                document.getElementById('registerForm').submit();
            }, 1200);
        }
    }

    function prevStep() {
        if (currentStep > 1) { currentStep--; showStep(currentStep); }
    }

    // ---- Summary (step 4) ----
    function generateSummary() {
        const f  = document.getElementById('registerForm');
        const ws = document.getElementById('wilaya_select');
        const cs = document.getElementById('city_select');
        const wt = ws.options[ws.selectedIndex]?.text || '-';
        const ct = cs.options[cs.selectedIndex]?.text || '-';
        const bankVal = f.bank_name.value === 'Autre'
                      ? (f.bank_other?.value || 'Autre')
                      : f.bank_name.value;
        const ribVal = f.rib.value;
        const ribMasked = ribVal.length === 20
                        ? ribVal.slice(0,4) + ' **** **** **** ' + ribVal.slice(16)
                        : ribVal;

        document.getElementById('summary').innerHTML = `
            <div class="grid grid-cols-2 gap-x-8 gap-y-3">
                <div>
                    <span class="text-[9px] uppercase text-teal-500 block font-bold">Nom complet</span>
                    ${escHtml(f.first_name.value)} ${escHtml(f.last_name.value)}
                </div>
                <div>
                    <span class="text-[9px] uppercase text-teal-500 block font-bold">Nom d'utilisateur</span>
                    ${escHtml(f.username.value)}
                </div>
                <div>
                    <span class="text-[9px] uppercase text-teal-500 block font-bold">Email</span>
                    ${escHtml(f.email.value)}
                </div>
                <div>
                    <span class="text-[9px] uppercase text-teal-500 block font-bold">Téléphone</span>
                    ${escHtml(f.phone.value)}
                </div>
                <div>
                    <span class="text-[9px] uppercase text-teal-500 block font-bold">NIF</span>
                    ${escHtml(f.nif.value)}
                </div>
                <div>
                    <span class="text-[9px] uppercase text-teal-500 block font-bold">RC</span>
                    ${escHtml(f.rc.value)}
                </div>
                <div>
                    <span class="text-[9px] uppercase text-teal-500 block font-bold">RIB</span>
                    <span class="font-mono">${escHtml(ribMasked)}</span>
                </div>
                <div>
                    <span class="text-[9px] uppercase text-teal-500 block font-bold">Banque</span>
                    ${escHtml(bankVal)}
                </div>
                <div class="col-span-2">
                    <span class="text-[9px] uppercase text-teal-500 block font-bold">Localisation</span>
                    ${escHtml(ct)}, ${escHtml(wt)}
                </div>
                <div class="col-span-2">
                    <span class="text-[9px] uppercase text-teal-500 block font-bold">Adresse</span>
                    ${escHtml(f.address.value)}
                </div>
            </div>
        `;
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }

    // Init
    showStep(1);
    </script>
</body>
</html>