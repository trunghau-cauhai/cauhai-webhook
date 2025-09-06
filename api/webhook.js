// api/webhook.js — Verify X-Hub-Signature-256 + quick 200 + de-dup
const crypto = require("crypto");

const VERIFY_TOKEN = process.env.VERIFY_TOKEN || "cauhai_verify_123";
const APP_SECRET   = process.env.FB_APP_SECRET;
const PAGE_TOKEN   = process.env.PAGE_TOKEN;

// --- simple de-dup (in-memory) for message mids, with TTL ~ 36h
const seen = new Map(); // mid -> expiresAt (ms)
const TTL_MS = 36 * 60 * 60 * 1000;
function isDup(mid) {
  const now = Date.now();
  // cleanup occasionally
  if (seen.size > 5000) {
    for (const [k, exp] of seen) if (exp < now) seen.delete(k);
  }
  const exp = seen.get(mid);
  if (exp && exp > now) return true;
  seen.set(mid, now + TTL_MS);
  return false;
}

module.exports = async (req, res) => {
  if (req.method === "GET") {
    const mode = req.query["hub.mode"];
    const token = req.query["hub.verify_token"];
    const challenge = req.query["hub.challenge"];
    return (mode === "subscribe" && token === VERIFY_TOKEN)
      ? res.status(200).send(challenge)
      : res.status(403).send("Forbidden");
  }

  if (req.method !== "POST") return res.status(405).send("Method Not Allowed");

  // 1) Đọc raw body để tính HMAC
  const raw = await readRawBody(req);

  // 2) Xác thực chữ ký (khuyến nghị của FB)
  if (APP_SECRET) {
    const ok = verifySig256(req.headers["x-hub-signature-256"], raw, APP_SECRET)
            || verifySig1(req.headers["x-hub-signature"], raw, APP_SECRET); // fallback
    if (!ok) return res.status(401).send("Invalid signature");
  }

  // 3) Parse JSON
  let body = {};
  try { body = JSON.parse(raw || "{}"); }
  catch { return res.status(400).send("Bad JSON"); }

  // 4) FB có thể batch nhiều bản cập nhật; phải 200 OK cho toàn bộ
  if (body.object !== "page") return res.status(200).send("IGNORED"); // vẫn 200 để FB không retry

  try {
    for (const entry of body.entry || []) {
      for (const e of entry.messaging || []) {
        if (e?.message?.is_echo) continue;

        const psid = e?.sender?.id;
        if (!psid || !PAGE_TOKEN) continue;

        // De-dup theo message.mid (nếu có)
        const mid = e?.message?.mid;
        if (mid && isDup(mid)) continue;

        if (e.postback?.payload === "GET_STARTED") {
          await sendText(psid, "Chào mừng đến Cậu Hai Home Decor ✨ Gõ 'menu' để xem gợi ý.");
        } else if (e.message?.text) {
          const t = e.message.text.trim().toLowerCase();
          if (t === "menu") {
            await sendText(psid,
              "Menu:\n- Trang trí: https://cauhaihomedecor.vn/collections/trang-tri\n- Gối/Chăn: https://cauhaihomedecor.vn/collections/vo-goi-sofa\n- Bếp/Bàn ăn: https://cauhaihomedecor.vn/collections/nha-bep-ban-an"
            );
          } else {
            await sendText(psid, `Bạn vừa nói: ${e.message.text}`);
          }
        } else if (e.message?.attachments?.length) {
          await sendText(psid, "Cảm ơn bạn đã gửi đính kèm.");
        }
      }
    }
  } catch (err) {
    console.error("[WEBHOOK ERROR]", err);
    // vẫn trả 200 để Facebook không retry vô hạn; mình tự xử lý retry/queue phía sau
  }

  // 5) Phải luôn trả 200 OK nhanh (FB sẽ retry tối đa ~36h nếu fail)
  return res.status(200).send("EVENT_RECEIVED");
};

// ---- helpers ----
async function sendText(psid, text) {
  const url = `https://graph.facebook.com/v23.0/me/messages?access_token=${PAGE_TOKEN}`;
  await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ recipient: { id: psid }, message: { text } })
  });
}

function verifySig256(header, raw, secret) {
  if (!header || !header.startsWith("sha256=")) return false;
  const provided = header.slice(7);
  const expected = crypto.createHmac("sha256", secret).update(raw).digest("hex");
  try {
    return crypto.timingSafeEqual(Buffer.from(provided, "hex"), Buffer.from(expected, "hex"));
  } catch { return false; }
}
function verifySig1(header, raw, secret) {
  if (!header || !header.startsWith("sha1=")) return false;
  const provided = header.slice(5);
  const expected = crypto.createHmac("sha1", secret).update(raw).digest("hex");
  try {
    return crypto.timingSafeEqual(Buffer.from(provided, "hex"), Buffer.from(expected, "hex"));
  } catch { return false; }
}

function readRawBody(req) {
  return new Promise((resolve, reject) => {
    let data = "";
    req.on("data", c => data += c);
    req.on("end", () => resolve(data));
    req.on("error", reject);
  });
}
