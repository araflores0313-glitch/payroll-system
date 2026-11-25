const SUPABASE_URL = "https://ksxktshensvtxaitbnna.supabase.co";
const SUPABASE_KEY = "sb_publishable_R5oUeKuWPwzJAE3tYLH8qQ_7srRjtBZ";

const db = supabase.createClient(SUPABASE_URL, SUPABASE_KEY);

function requireAuth() {
    if (localStorage.getItem("admin_logged_in") !== "true") {
        window.location.href = "login.html";
    }
}

function logoutAdmin() {
    localStorage.removeItem("admin_logged_in");
    localStorage.removeItem("admin_id");
    localStorage.removeItem("admin_name");
    window.location.href = "login.html";
}

function escapeHtml(str) {
    if (str === null || str === undefined) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}