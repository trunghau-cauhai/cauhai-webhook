// api/fb-data-deletion.js
// Facebook Data Deletion Callback (Vercel/Node)
// Docs: must return JSON { url: '<status-url>', confirmation_code: '<code>' }

const APP_SECRET = process.env.FB_APP_SECRET; // đặt trong Vercel → Settings → Environment Variables

module.exports = async (req, res) => {
  try {
    if (req.method !== "POST") return res.status(405).send("Method Not Allowed");
    if (!APP_SECRET) return res.status(500).json({ error: "missing_FB_APP_SECRET_env" });

    // Facebook gửi "application/x-www-form-urlencoded" với trường signed_request.
    const body = await readRawBody(req);
    const params = parseForm(body);
    const signed = params.signed_request;
    if (!signed) return res.status(400).json({ error: "missing_signed_request" });

    // 1) Parse & verify signed_request
    const data = parseSignedRequest(signed, APP_SECRET);
    if (!data || !data.user_id) return res.status(401).json({ error: "invalid_signature_or_payload", data });

    const userId = String(data.user_id);
    // 2) TODO: Xoá dữ liệu userId trong hệ thống của anh (DB, logs, cache...) ở đây.
    //    Ví dụ: await db.users.delete({ fb_user_id: userId })

    // 3) Trả JSON theo format FB yêu cầu
    const code = `del_${userId}_${Date.now().toString(36)}`;
    const statusUrl = `https://cauhaihomedecor.vn/pages/data-deletion?code=${encodeURIComponent(code)}`;

    res.setHeader("Content-Type", "application/json");
    // ĐÚNG format: 'url' và 'confirmation_code'
    return res.status(200).end(JSON.stringify({
      url: statusUrl,
      confirmation_code: code
    }));
  } catch (e) {
    console.error("[fb-data-deletion] error:", e);
    return res.status(500).json({ error: "server_error" });
  }
};

// -------- helpers ----------
function readRawBody(req) {
  return new Promise((resolve, reject) => {
    let data = "";
    req.on("data", chunk => (data += chunk));
    req.on("end", () => resolve(data));
    req.on("error", reject);
  });
}

function parseForm(str = "") {
  const out = {};
  str.split("&").forEach(kv => {
    const [k, v] = kv.split("=");
    if (!k) return;
    out[decodeURIComponent(k)] = decodeURIComponent((v || "").replace(/\+/g, " "));
  });
  return out;
}

function parseSignedRequest(signedRequest, appSecret) {
  const [encodedSig, encodedPayload] = signedRequest.split(".", 2);
  if (!encodedSig || !encodedPayload) return null;

  const sig = base64UrlDecodeToBuf(encodedSig);
  const payload = JSON.parse(base64UrlDecode(encodedPayload));

  if (!payload.algorithm || payload.algorithm.toUpperCase() !== "HMAC-SHA256") return null;

  const crypto = require("crypto");
  const expectedSig = crypto
    .createHmac("sha256", appSecret)
    .update(encodedPayload) // quan trọng: HMAC trên phần payload CHƯA decode (base64url string)
    .digest();

  // so sánh an toàn độ dài
  if (sig.length !== expectedSig.length || !crypto.timingSafeEqual(sig, expectedSig)) return null;
  return payload; // { user_id, issued_at, ... }
}

function base64UrlDecode(str) {
  str = str.replace(/-/g, "+").replace(/_/g, "/");
  // padding
  while (str.length % 4) str += "=";
  return Buffer.from(str, "base64").toString("utf8");
}

function base64UrlDecodeToBuf(str) {
  str = str.replace(/-/g, "+").replace(/_/g, "/");
  while (str.length % 4) str += "=";
  return Buffer.from(str, "base64");
}
