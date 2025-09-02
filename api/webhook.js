const express = require("express");
const bodyParser = require("body-parser");
const axios = require("axios");

const app = express();
app.use(bodyParser.json());

// Lấy token từ biến môi trường
const VERIFY_TOKEN = process.env.VERIFY_TOKEN || "caha_verify_token";
const PAGE_TOKEN   = process.env.PAGE_ACCESS_TOKEN || "";

// Route GET để verify
app.get("/api/webhook", (req, res) => {
  const mode = req.query["hub.mode"];
  const token = req.query["hub.verify_token"];
  const challenge = req.query["hub.challenge"];

  if (mode === "subscribe" && token === VERIFY_TOKEN) {
    return res.status(200).send(challenge);
  }
  return res.sendStatus(403);
});

// Route POST để nhận message
app.post("/api/webhook", async (req, res) => {
  res.status(200).send("EVENT_RECEIVED");

  try {
    if (req.body.object !== "page") return;

    for (const entry of req.body.entry || []) {
      for (const ev of entry.messaging || []) {
        const psid = ev.sender?.id;
        if (!psid) continue;

        if (ev.message?.text) {
          await sendText(psid, `CAHA 👋 Anh/chị vừa nhắn: "${ev.message.text}"`);
        }
      }
    }
  } catch (e) {
    console.error("Handler error:", e?.response?.data || e);
  }
});

// Hàm gửi tin nhắn
async function sendText(psid, text) {
  if (!PAGE_TOKEN) return;
  const url = `https://graph.facebook.com/v23.0/me/messages?access_token=${PAGE_TOKEN}`;
  await axios.post(url, {
    recipient: { id: psid },
    message: { text }
  });
}

// Export cho Vercel
module.exports = app;
