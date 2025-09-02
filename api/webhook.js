// api/webhook.js
const express = require("express");
const bodyParser = require("body-parser");
const axios = require("axios");
const serverless = require("serverless-http");

const app = express();
app.use(bodyParser.json());

// Lấy token từ Vercel Environment Variables
const VERIFY_TOKEN = process.env.VERIFY_TOKEN || "caha_verify_token";
const PAGE_TOKEN   = process.env.PAGE_ACCESS_TOKEN || "";

/**
 * GET "/" (trên endpoint /api/webhook)
 * - Nếu có tham số hub.* => flow VERIFY của Facebook
 * - Nếu không => trả về message kiểm tra nhanh
 */
app.get("/", (req, res) => {
  const mode = req.query["hub.mode"];
  const token = req.query["hub.verify_token"];
  const challenge = req.query["hub.challenge"];

  // Flow VERIFY của FB
  if (mode && token) {
    if (mode === "subscribe" && token === VERIFY_TOKEN) {
      console.log("✅ Verified webhook");
      return res.status(200).send(challenge);
    }
    return res.sendStatus(403); // sai verify_token
  }

  // Trang kiểm tra nhanh khi mở trình duyệt
  return res.status(200).send("CAHA Webhook is running");
});

/**
 * POST "/" (trên endpoint /api/webhook)
 * - FB sẽ gọi khi có tin nhắn / postback
 */
app.post("/", async (req, res) => {
  // Báo FB là đã nhận (quan trọng)
  res.status(200).send("EVENT_RECEIVED");

  try {
    if (req.body.object !== "page") return;

    for (const entry of req.body.entry || []) {
      for (const ev of entry.messaging || []) {
        const psid = ev.sender?.id;
        if (!psid) continue;

        if (ev.message?.text) {
          await sendText(psid, `CAHA xin chào 👋 Anh/chị vừa nhắn: "${ev.message.text}"`);
        } else if (ev.postback?.payload) {
          await sendText(psid, `Anh/chị vừa bấm: ${ev.postback.payload}`);
        }
      }
    }
  } catch (e) {
    console.error("Handler error:", e?.response?.data || e);
  }
});

// Gọi Send API để trả lời khách
async function sendText(psid, text) {
  if (!PAGE_TOKEN) {
    console.warn("⚠️ Chưa cấu hình PAGE_ACCESS_TOKEN trên Vercel");
    return;
  }
  const url = `https://graph.facebook.com/v23.0/me/messages?access_token=${PAGE_TOKEN}`;
  await axios.post(url, {
    recipient: { id: psid },
    message: { text }
  });
}

// ✨ Vercel cần export function, KHÔNG dùng app.listen()
module.exports = app;              // cho dev/local
module.exports.handler = serverless(app); // cho Vercel
