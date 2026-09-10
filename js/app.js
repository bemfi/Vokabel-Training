/* Vokabeltrainer – Frontend-Logik */
"use strict";

const API = "api";

// ---------------------------------------------------------------
// Übersetzungen (DE / EN)
// ---------------------------------------------------------------
const I18N = {
    de: {
        appTitle: "Vokabeltrainer",
        login: "Anmelden", register: "Registrieren", logout: "Abmelden",
        email: "E-Mail", username: "Benutzername", password: "Passwort",
        passwordHint: "Mindestens 8 Zeichen, mit Buchstabe und Zahl.",
        forgotPassword: "Passwort vergessen?",
        resetTitle: "Passwort zurücksetzen",
        resetInfo: "Gib deine E-Mail-Adresse ein. Du erhältst einen Link zum Zurücksetzen.",
        sendResetLink: "Link senden", backToLogin: "← Zurück zur Anmeldung",
        newPasswordTitle: "Neues Passwort setzen", newPassword: "Neues Passwort",
        repeatPassword: "Passwort wiederholen", savePassword: "Passwort speichern",
        passwordMismatch: "Die Passwörter stimmen nicht überein.",
        myDatasets: "Meine Datensätze", newDataset: "+ Neuer Datensatz",
        newDatasetTitle: "Neuer Datensatz", editDatasetTitle: "Datensatz bearbeiten",
        datasetName: "Name des Datensatzes", datasetNamePh: "z.B. Italienisch Lektion 1",
        language1: "Sprache 1", language2: "Sprache 2", language3: "Sprache 3 (optional)",
        requiredCorrect: 'Richtig-Antworten bis "gekonnt" (pro Richtung)',
        resetOnWrong: "Zähler bei Fehler auf 0 zurücksetzen",
        save: "Speichern", cancel: "Abbrechen", add: "Hinzufügen", back: "← Zurück",
        noDatasets: "Noch keine Datensätze. Lege deinen ersten an!",
        train: "Trainieren", vocabBtn: "Vokabeln",
        deleteDatasetConfirm: (n) => `Datensatz "${n}" mit allen Vokabeln löschen?`,
        vocabCountLabel: (c) => `${c} Vokabeln`,
        masteredLabel: (m, t) => `${m} von ${t} gekonnt`,
        requiredLabel: (n) => `gekonnt ab ${n}× richtig`,
        addVocab: "Vokabel hinzufügen", note: "Notiz",
        importTitle: "Excel / CSV importieren",
        importHint: "Spaltenaufbau: Spalte A = Sprache 1, Spalte B = Sprache 2, Spalte C = Sprache 3 (optional), Spalte D = Notiz (optional). Erste Zeile darf Überschriften enthalten.",
        importReading: "Lese Datei …",
        importOk: (i) => `✅ ${i} Vokabeln importiert`,
        importSkipped: (s) => `, ${s} Zeilen übersprungen (unvollständig).`,
        importFail: (m) => `❌ Import fehlgeschlagen: ${m}`,
        vocabList: "Vokabelliste", status: "Status", mastered: "gekonnt", openBadge: "offen",
        deleteVocabConfirm: (w) => `"${w}" löschen?`,
        directions: "Abfragerichtungen", startTraining: "Training starten",
        selectDirection: "Bitte mindestens eine Abfragerichtung wählen.",
        open: "Offen", check: "Prüfen", next: "Weiter →", answerPh: "Antwort eingeben …",
        doneTitle: "🎉 Geschafft!",
        resetProgress: "Fortschritt zurücksetzen", toOverview: "Zur Übersicht",
        progress: "Fortschritt",
        trainingTitle: (n) => `Training: ${n}`,
        emptyDataset: "Dieser Datensatz enthält noch keine Vokabeln.",
        allMastered: (c, a) => `Alle Vokabeln in den gewählten Richtungen gekonnt! (Session: ${c}/${a} richtig)`,
        correctMastered: (c, r) => `✅ Richtig! Diese Richtung ist jetzt gekonnt (${c}/${r}).`,
        correctFb: (c, r) => `✅ Richtig! (${c}/${r})`,
        wrongFb: (e) => `❌ Falsch. Richtige Antwort: "${e}"`,
        resetProgressConfirm: "Gesamten Lernfortschritt dieses Datensatzes zurücksetzen?",
        statsText: (mw, vt, cm, ct) => `${mw} von ${vt} Vokabeln vollständig gekonnt · ${cm} von ${ct} Richtungs-Kombinationen gemeistert`,
    },
    en: {
        appTitle: "Vocabulary Trainer",
        login: "Sign in", register: "Sign up", logout: "Sign out",
        email: "Email", username: "Username", password: "Password",
        passwordHint: "At least 8 characters, with a letter and a number.",
        forgotPassword: "Forgot password?",
        resetTitle: "Reset password",
        resetInfo: "Enter your email address. You will receive a reset link.",
        sendResetLink: "Send link", backToLogin: "← Back to sign in",
        newPasswordTitle: "Set new password", newPassword: "New password",
        repeatPassword: "Repeat password", savePassword: "Save password",
        passwordMismatch: "Passwords do not match.",
        myDatasets: "My datasets", newDataset: "+ New dataset",
        newDatasetTitle: "New dataset", editDatasetTitle: "Edit dataset",
        datasetName: "Dataset name", datasetNamePh: "e.g. Italian lesson 1",
        language1: "Language 1", language2: "Language 2", language3: "Language 3 (optional)",
        requiredCorrect: 'Correct answers until "mastered" (per direction)',
        resetOnWrong: "Reset counter to 0 on wrong answer",
        save: "Save", cancel: "Cancel", add: "Add", back: "← Back",
        noDatasets: "No datasets yet. Create your first one!",
        train: "Train", vocabBtn: "Vocabulary",
        deleteDatasetConfirm: (n) => `Delete dataset "${n}" with all its vocabulary?`,
        vocabCountLabel: (c) => `${c} words`,
        masteredLabel: (m, t) => `${m} of ${t} mastered`,
        requiredLabel: (n) => `mastered after ${n}× correct`,
        addVocab: "Add vocabulary", note: "Note",
        importTitle: "Import Excel / CSV",
        importHint: "Column layout: column A = language 1, column B = language 2, column C = language 3 (optional), column D = note (optional). First row may contain headers.",
        importReading: "Reading file …",
        importOk: (i) => `✅ ${i} words imported`,
        importSkipped: (s) => `, ${s} rows skipped (incomplete).`,
        importFail: (m) => `❌ Import failed: ${m}`,
        vocabList: "Vocabulary list", status: "Status", mastered: "mastered", openBadge: "open",
        deleteVocabConfirm: (w) => `Delete "${w}"?`,
        directions: "Quiz directions", startTraining: "Start training",
        selectDirection: "Please select at least one direction.",
        open: "Open", check: "Check", next: "Next →", answerPh: "Type your answer …",
        doneTitle: "🎉 Done!",
        resetProgress: "Reset progress", toOverview: "Back to overview",
        progress: "Progress",
        trainingTitle: (n) => `Training: ${n}`,
        emptyDataset: "This dataset has no vocabulary yet.",
        allMastered: (c, a) => `All words mastered in the selected directions! (Session: ${c}/${a} correct)`,
        correctMastered: (c, r) => `✅ Correct! This direction is now mastered (${c}/${r}).`,
        correctFb: (c, r) => `✅ Correct! (${c}/${r})`,
        wrongFb: (e) => `❌ Wrong. Correct answer: "${e}"`,
        resetProgressConfirm: "Reset all learning progress for this dataset?",
        statsText: (mw, vt, cm, ct) => `${mw} of ${vt} words fully mastered · ${cm} of ${ct} direction combinations mastered`,
    },
};

let lang = localStorage.getItem("vt-lang") || (navigator.language.startsWith("de") ? "de" : "en");
const t = (key, ...args) => {
    const val = I18N[lang][key] ?? I18N.de[key] ?? key;
    return typeof val === "function" ? val(...args) : val;
};

function applyI18n() {
    document.documentElement.lang = lang;
    document.title = t("appTitle");
    document.querySelectorAll("[data-i18n]").forEach((el) => {
        el.textContent = t(el.dataset.i18n);
    });
    document.querySelectorAll("[data-i18n-placeholder]").forEach((el) => {
        el.placeholder = t(el.dataset.i18nPlaceholder);
    });
    document.getElementById("lang-label").textContent = lang === "de" ? "EN" : "DE";
    updateAuthSubmitLabel();
}

// ---------------------------------------------------------------
// Theme (Dark / Light)
// ---------------------------------------------------------------
let theme = localStorage.getItem("vt-theme")
    || (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");

function applyTheme() {
    document.documentElement.dataset.theme = theme;
    document.getElementById("btn-theme").textContent = theme === "dark" ? "☀️" : "🌙";
}

// ---------------------------------------------------------------
// Hilfsfunktionen
// ---------------------------------------------------------------
const $ = (id) => document.getElementById(id);

let csrfToken = "";

async function api(path, options = {}) {
    const headers = { "Content-Type": "application/json" };
    if (csrfToken) headers["X-CSRF-Token"] = csrfToken;
    const res = await fetch(`${API}/${path}`, {
        headers,
        credentials: "same-origin",
        ...options,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw new Error(data.error || `Error (${res.status})`);
    }
    if (data.csrf) csrfToken = data.csrf;
    return data;
}

function post(path, body) {
    return api(path, { method: "POST", body: JSON.stringify(body) });
}

function showView(id) {
    document.querySelectorAll(".view").forEach((v) => v.classList.add("hidden"));
    $(id).classList.remove("hidden");
}

function escapeHtml(s) {
    const div = document.createElement("div");
    div.textContent = s ?? "";
    return div.innerHTML;
}

// ---------------------------------------------------------------
// Zustand
// ---------------------------------------------------------------
let currentDataset = null;   // aktuell geöffneter Datensatz
let currentQuestion = null;  // aktuelle Trainingsfrage
let sessionStats = { asked: 0, correct: 0 };

// ---------------------------------------------------------------
// Auth
// ---------------------------------------------------------------
let authMode = "login";

function updateAuthSubmitLabel() {
    $("btn-auth-submit").textContent = t(authMode === "login" ? "login" : "register");
}

document.querySelectorAll(".tab").forEach((tab) => {
    tab.addEventListener("click", () => {
        document.querySelectorAll(".tab").forEach((el) => el.classList.remove("active"));
        tab.classList.add("active");
        authMode = tab.dataset.tab;
        const isRegister = authMode === "register";
        $("wrap-auth-username").classList.toggle("hidden", !isRegister);
        $("auth-username").required = isRegister;
        $("password-hint").classList.toggle("hidden", !isRegister);
        $("auth-password").autocomplete = isRegister ? "new-password" : "current-password";
        updateAuthSubmitLabel();
        $("auth-error").classList.add("hidden");
    });
});

$("form-auth").addEventListener("submit", async (e) => {
    e.preventDefault();
    $("auth-error").classList.add("hidden");
    try {
        const payload = {
            email: $("auth-email").value,
            password: $("auth-password").value,
        };
        if (authMode === "register") {
            payload.username = $("auth-username").value;
        }
        const data = await post(`auth.php?action=${authMode === "login" ? "login" : "register"}`, payload);
        onLoggedIn(data.username);
    } catch (err) {
        $("auth-error").textContent = err.message;
        $("auth-error").classList.remove("hidden");
    }
});

$("btn-logout").addEventListener("click", async () => {
    try { await post("auth.php?action=logout", {}); } catch (_) { /* Session evtl. abgelaufen */ }
    csrfToken = "";
    $("user-info").classList.add("hidden");
    showView("view-auth");
});

function onLoggedIn(username) {
    $("username-display").textContent = `👤 ${username}`;
    $("user-info").classList.remove("hidden");
    loadDatasets();
    showView("view-datasets");
}

async function checkSession() {
    // Passwort-Reset-Link aus URL-Hash (#reset=TOKEN)
    const match = location.hash.match(/^#reset=([a-f0-9]{64})$/);
    if (match) {
        resetToken = match[1];
        history.replaceState(null, "", location.pathname);
        showView("view-reset");
        return;
    }
    try {
        const data = await api("auth.php?action=me");
        if (data.loggedIn) {
            onLoggedIn(data.username);
            return;
        }
    } catch (_) { /* ignorieren */ }
    showView("view-auth");
}

// ---------------------------------------------------------------
// Passwort vergessen / zurücksetzen
// ---------------------------------------------------------------
let resetToken = "";

$("link-forgot").addEventListener("click", (e) => {
    e.preventDefault();
    $("forgot-message").classList.add("hidden");
    showView("view-forgot");
});

$("link-back-login").addEventListener("click", (e) => {
    e.preventDefault();
    showView("view-auth");
});

$("form-forgot").addEventListener("submit", async (e) => {
    e.preventDefault();
    const msg = $("forgot-message");
    msg.classList.add("hidden");
    try {
        const data = await post("auth.php?action=request_reset", {
            email: $("forgot-email").value,
        });
        msg.textContent = data.message;
        msg.classList.remove("hidden");
    } catch (err) {
        msg.textContent = err.message;
        msg.classList.remove("hidden");
    }
});

$("form-reset").addEventListener("submit", async (e) => {
    e.preventDefault();
    const msg = $("reset-message");
    msg.classList.add("hidden");

    const p1 = $("reset-password").value;
    const p2 = $("reset-password2").value;
    if (p1 !== p2) {
        msg.textContent = t("passwordMismatch");
        msg.classList.remove("hidden");
        return;
    }
    try {
        const data = await post("auth.php?action=reset_password", {
            token: resetToken,
            password: p1,
        });
        alert(data.message);
        resetToken = "";
        showView("view-auth");
    } catch (err) {
        msg.textContent = err.message;
        msg.classList.remove("hidden");
    }
});

// ---------------------------------------------------------------
// Datensätze
// ---------------------------------------------------------------
async function loadDatasets() {
    const data = await api("datasets.php?action=list");
    const wrap = $("dataset-list");
    wrap.innerHTML = "";

    if (data.datasets.length === 0) {
        wrap.innerHTML = `<p class="hint">${escapeHtml(t("noDatasets"))}</p>`;
        return;
    }

    for (const ds of data.datasets) {
        const langs = [ds.lang1, ds.lang2, ds.lang3].filter(Boolean).join(" · ");
        const total = parseInt(ds.vocab_count, 10);
        const mastered = parseInt(ds.mastered_count ?? 0, 10);
        const pct = total > 0 ? Math.round((mastered / total) * 100) : 0;
        const card = document.createElement("div");
        card.className = "dataset-card";
        card.innerHTML = `
            <h3>${escapeHtml(ds.name)}</h3>
            <div class="langs">${escapeHtml(langs)}</div>
            <div class="ds-progress"><div class="ds-progress-inner" style="width:${pct}%"></div></div>
            <div class="meta">${escapeHtml(t("vocabCountLabel", total))} · ${escapeHtml(t("masteredLabel", mastered, total))}</div>
            <div class="meta">${escapeHtml(t("requiredLabel", ds.required_correct))}</div>
            <div class="btn-row">
                <button class="btn btn-primary btn-small" data-act="train">${escapeHtml(t("train"))}</button>
                <button class="btn btn-secondary btn-small" data-act="edit-vocab">${escapeHtml(t("vocabBtn"))}</button>
                <button class="btn btn-secondary btn-small" data-act="settings">⚙️</button>
                <button class="btn btn-danger btn-small" data-act="delete">🗑</button>
            </div>`;
        card.querySelector('[data-act="train"]').addEventListener("click", () => openTraining(ds));
        card.querySelector('[data-act="edit-vocab"]').addEventListener("click", () => openVocab(ds));
        card.querySelector('[data-act="settings"]').addEventListener("click", () => openDatasetForm(ds));
        card.querySelector('[data-act="delete"]').addEventListener("click", async () => {
            if (!confirm(t("deleteDatasetConfirm", ds.name))) return;
            await post("datasets.php?action=delete", { id: ds.id });
            loadDatasets();
        });
        wrap.appendChild(card);
    }
}

function openDatasetForm(ds = null) {
    $("dataset-form-wrap").classList.remove("hidden");
    $("dataset-form-title").textContent = t(ds ? "editDatasetTitle" : "newDatasetTitle");
    $("ds-id").value = ds ? ds.id : "";
    $("ds-name").value = ds ? ds.name : "";
    $("ds-lang1").value = ds ? ds.lang1 : "";
    $("ds-lang2").value = ds ? ds.lang2 : "";
    $("ds-lang3").value = ds && ds.lang3 ? ds.lang3 : "";
    $("ds-required").value = ds ? ds.required_correct : 3;
    $("ds-reset").checked = ds ? ds.reset_on_wrong == 1 : true;
    $("ds-name").focus();
}

$("btn-new-dataset").addEventListener("click", () => openDatasetForm());
$("btn-dataset-cancel").addEventListener("click", () => $("dataset-form-wrap").classList.add("hidden"));

$("form-dataset").addEventListener("submit", async (e) => {
    e.preventDefault();
    const id = $("ds-id").value;
    const payload = {
        name: $("ds-name").value,
        lang1: $("ds-lang1").value,
        lang2: $("ds-lang2").value,
        lang3: $("ds-lang3").value,
        required_correct: parseInt($("ds-required").value, 10) || 3,
        reset_on_wrong: $("ds-reset").checked,
    };
    try {
        if (id) {
            await post("datasets.php?action=update", { id: parseInt(id, 10), ...payload });
        } else {
            await post("datasets.php?action=create", payload);
        }
        $("dataset-form-wrap").classList.add("hidden");
        loadDatasets();
    } catch (err) {
        alert(err.message);
    }
});

// ---------------------------------------------------------------
// Vokabeln verwalten
// ---------------------------------------------------------------
function openVocab(ds) {
    currentDataset = ds;
    $("vocab-title").textContent = ds.name;
    $("lbl-word1").textContent = ds.lang1;
    $("lbl-word2").textContent = ds.lang2;
    if (ds.lang3) {
        $("wrap-word3").classList.remove("hidden");
        $("lbl-word3").textContent = ds.lang3;
    } else {
        $("wrap-word3").classList.add("hidden");
    }
    resetVocabForm();
    $("import-result").textContent = "";
    $("file-import").value = "";
    loadVocab();
    showView("view-vocab");
}

$("btn-back-datasets").addEventListener("click", () => {
    loadDatasets();
    showView("view-datasets");
});

async function loadVocab() {
    const data = await api(`vocab.php?action=list&dataset_id=${currentDataset.id}`);
    const ds = currentDataset;
    const thead = $("vocab-thead-row");
    thead.innerHTML =
        `<th>${escapeHtml(ds.lang1)}</th><th>${escapeHtml(ds.lang2)}</th>` +
        (ds.lang3 ? `<th>${escapeHtml(ds.lang3)}</th>` : "") +
        `<th>${escapeHtml(t("note"))}</th><th>${escapeHtml(t("status"))}</th><th></th>`;

    const tbody = $("vocab-tbody");
    tbody.innerHTML = "";
    $("vocab-count").textContent = data.vocab.length;

    for (const v of data.vocab) {
        const tr = document.createElement("tr");
        const mastered = v.directions_tracked > 0 && parseInt(v.min_correct, 10) >= ds.required_correct;
        tr.innerHTML =
            `<td>${escapeHtml(v.word1)}</td><td>${escapeHtml(v.word2)}</td>` +
            (ds.lang3 ? `<td>${escapeHtml(v.word3 || "")}</td>` : "") +
            `<td>${escapeHtml(v.note || "")}</td>` +
            `<td><span class="badge ${mastered ? "badge-mastered" : "badge-open"}">${escapeHtml(t(mastered ? "mastered" : "openBadge"))}</span></td>` +
            `<td>
                <button class="btn btn-secondary btn-small" data-act="edit">✏️</button>
                <button class="btn btn-danger btn-small" data-act="del">🗑</button>
            </td>`;
        tr.querySelector('[data-act="edit"]').addEventListener("click", () => {
            $("v-id").value = v.id;
            $("v-word1").value = v.word1;
            $("v-word2").value = v.word2;
            $("v-word3").value = v.word3 || "";
            $("v-note").value = v.note || "";
            $("btn-vocab-submit").textContent = t("save");
            $("btn-vocab-cancel").classList.remove("hidden");
            $("v-word1").focus();
        });
        tr.querySelector('[data-act="del"]').addEventListener("click", async () => {
            if (!confirm(t("deleteVocabConfirm", v.word1))) return;
            await post("vocab.php?action=delete", { id: v.id });
            loadVocab();
        });
        tbody.appendChild(tr);
    }
}

function resetVocabForm() {
    $("v-id").value = "";
    $("form-vocab").reset();
    $("btn-vocab-submit").textContent = t("add");
    $("btn-vocab-cancel").classList.add("hidden");
}

$("btn-vocab-cancel").addEventListener("click", resetVocabForm);

$("form-vocab").addEventListener("submit", async (e) => {
    e.preventDefault();
    const id = $("v-id").value;
    const payload = {
        word1: $("v-word1").value,
        word2: $("v-word2").value,
        word3: $("v-word3").value,
        note: $("v-note").value,
    };
    try {
        if (id) {
            await post("vocab.php?action=update", { id: parseInt(id, 10), ...payload });
        } else {
            await post("vocab.php?action=add", { dataset_id: currentDataset.id, ...payload });
        }
        resetVocabForm();
        loadVocab();
        $("v-word1").focus();
    } catch (err) {
        alert(err.message);
    }
});

// ---------------------------------------------------------------
// Excel/CSV-Import (SheetJS parst im Browser, Server bekommt JSON)
// ---------------------------------------------------------------
$("file-import").addEventListener("change", async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    $("import-result").textContent = t("importReading");

    try {
        const buf = await file.arrayBuffer();
        const wb = XLSX.read(buf, { type: "array" });
        const sheet = wb.Sheets[wb.SheetNames[0]];
        const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: "" });

        // Kopfzeile überspringen, wenn Zeile 1 keine echten Vokabeln enthält
        // (Heuristik: wenn eine Zelle dem Sprachnamen entspricht)
        let start = 0;
        if (rows.length > 0) {
            const first = rows[0].map((c) => String(c).trim().toLowerCase());
            const langNames = [currentDataset.lang1, currentDataset.lang2, currentDataset.lang3]
                .filter(Boolean).map((l) => l.toLowerCase());
            if (first.some((c) => langNames.includes(c) || ["wort 1", "wort1", "word1"].includes(c))) {
                start = 1;
            }
        }

        const payload = [];
        for (let i = start; i < rows.length; i++) {
            const r = rows[i];
            payload.push({
                word1: String(r[0] ?? "").trim(),
                word2: String(r[1] ?? "").trim(),
                word3: String(r[2] ?? "").trim(),
                note: String(r[3] ?? "").trim(),
            });
        }

        const result = await post("vocab.php?action=import", {
            dataset_id: currentDataset.id,
            rows: payload,
        });
        $("import-result").textContent =
            t("importOk", result.imported) +
            (result.skipped > 0 ? t("importSkipped", result.skipped) : ".");
        loadVocab();
    } catch (err) {
        $("import-result").textContent = t("importFail", err.message);
    }
    e.target.value = "";
});

// ---------------------------------------------------------------
// Training
// ---------------------------------------------------------------
function directionLabel(dir, ds) {
    const names = { 1: ds.lang1, 2: ds.lang2, 3: ds.lang3 };
    const [from, to] = dir.split(">");
    return `${names[from]} → ${names[to]}`;
}

function allDirections(ds) {
    const langs = ds.lang3 ? [1, 2, 3] : [1, 2];
    const dirs = [];
    for (const f of langs) for (const t of langs) if (f !== t) dirs.push(`${f}>${t}`);
    return dirs;
}

function openTraining(ds) {
    currentDataset = ds;
    sessionStats = { asked: 0, correct: 0 };
    $("training-title").textContent = t("trainingTitle", ds.name);
    $("training-card").classList.add("hidden");
    $("training-done").classList.add("hidden");
    $("training-settings").classList.remove("hidden");

    const wrap = $("direction-checkboxes");
    wrap.innerHTML = "";
    for (const dir of allDirections(ds)) {
        const label = document.createElement("label");
        label.innerHTML = `<input type="checkbox" value="${dir}" checked> ${escapeHtml(directionLabel(dir, ds))}`;
        wrap.appendChild(label);
    }

    loadStats();
    showView("view-training");
}

$("btn-back-training").addEventListener("click", () => {
    loadDatasets();
    showView("view-datasets");
});
$("btn-done-back").addEventListener("click", () => {
    loadDatasets();
    showView("view-datasets");
});

function selectedDirections() {
    return [...document.querySelectorAll("#direction-checkboxes input:checked")].map((c) => c.value);
}

$("btn-start-training").addEventListener("click", () => {
    if (selectedDirections().length === 0) {
        alert(t("selectDirection"));
        return;
    }
    $("training-settings").classList.add("hidden");
    $("training-card").classList.remove("hidden");
    $("training-done").classList.add("hidden");
    nextQuestion();
});

async function nextQuestion() {
    $("training-feedback").classList.add("hidden");
    $("btn-next").classList.add("hidden");
    $("btn-check").classList.remove("hidden");
    $("training-answer").value = "";
    $("training-answer").disabled = false;

    const dirs = selectedDirections().join(",");
    const data = await api(
        `training.php?action=next&dataset_id=${currentDataset.id}&directions=${encodeURIComponent(dirs)}`
    );

    if (data.done) {
        $("training-card").classList.add("hidden");
        $("training-done").classList.remove("hidden");
        $("training-done-text").textContent =
            data.reason === "empty"
                ? t("emptyDataset")
                : t("allMastered", sessionStats.correct, sessionStats.asked);
        loadStats();
        return;
    }

    currentQuestion = data;
    $("training-direction-label").textContent = `${data.question_lang} → ${data.answer_lang}`;
    $("training-open").textContent = data.open_total;
    $("training-question").textContent = data.question;
    $("training-note").textContent = data.note ? `💡 ${data.note}` : "";

    const pct = (data.correct_count / data.required_correct) * 100;
    $("training-progress-inner").style.width = `${pct}%`;

    $("training-answer").focus();
}

$("form-answer").addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!currentQuestion || $("btn-check").classList.contains("hidden")) return;

    const answer = $("training-answer").value;
    const result = await post("training.php?action=answer", {
        vocab_id: currentQuestion.vocab_id,
        direction: currentQuestion.direction,
        answer,
    });

    sessionStats.asked++;
    if (result.correct) sessionStats.correct++;

    const fb = $("training-feedback");
    fb.classList.remove("hidden", "correct", "wrong");
    if (result.correct) {
        fb.classList.add("correct");
        fb.textContent = result.mastered_direction
            ? t("correctMastered", result.correct_count, result.required_correct)
            : t("correctFb", result.correct_count, result.required_correct);
    } else {
        fb.classList.add("wrong");
        fb.textContent = t("wrongFb", result.expected);
    }

    $("training-answer").disabled = true;
    $("btn-check").classList.add("hidden");
    $("btn-next").classList.remove("hidden");
    $("btn-next").focus();
});

$("btn-next").addEventListener("click", nextQuestion);

// Enter drückt "Weiter", wenn Feedback angezeigt wird
document.addEventListener("keydown", (e) => {
    if (e.key === "Enter" && !$("btn-next").classList.contains("hidden")) {
        e.preventDefault();
        nextQuestion();
    }
});

$("btn-reset-progress").addEventListener("click", async () => {
    if (!confirm(t("resetProgressConfirm"))) return;
    await post("datasets.php?action=reset_progress", { id: currentDataset.id });
    openTraining(currentDataset);
});

async function loadStats() {
    try {
        const s = await api(`training.php?action=stats&dataset_id=${currentDataset.id}`);
        $("stats-text").textContent =
            t("statsText", s.mastered_words, s.vocab_total, s.combos_mastered, s.combos_total);
    } catch (_) {
        $("stats-text").textContent = "–";
    }
}

// ---------------------------------------------------------------
// Sprach- und Theme-Umschalter
// ---------------------------------------------------------------
$("btn-lang").addEventListener("click", () => {
    lang = lang === "de" ? "en" : "de";
    localStorage.setItem("vt-lang", lang);
    applyI18n();
});

$("btn-theme").addEventListener("click", () => {
    theme = theme === "dark" ? "light" : "dark";
    localStorage.setItem("vt-theme", theme);
    applyTheme();
});

// ---------------------------------------------------------------
// Start
// ---------------------------------------------------------------
applyTheme();
applyI18n();
checkSession();
