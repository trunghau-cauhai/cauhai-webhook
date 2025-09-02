const bodyParser = require("body-parser");
const axios = require("axios");

const VERIFY_TOKEN = process.env.VERIFY_TOKEN || "caha_verify_token";
const PAGE_TOKEN   = process.env.PAGE_ACCESS_TOKEN || "";

// Hàm xử lý request
module.exports = (req, res) => {
  if (req.method === "GET") {
    const mode = req.query["hub.mode"];
    const token = req.query["hub.verify_token"];
    const challenge = req.query["hub.challenge"];

    if (mode === "subscribe" && token === VERIFY_TOKEN) {
      console.log("✅ Verified webhook");
      return res.status(200).send(challenge);
    }
    return res.sendStatus(403);
  }

  if (req.method === "POST") {
    res.status(200).send("EVENT_RECEIVED");

    try {
      if (req.body.object !== "page") return;

      for (const entry of req.body.entry || []) {
        for (const ev of entry.messaging || []) {
          const psid = ev.sender?.id;
          if (!psid) continue;

          if (ev.message?.text) {
            sendText(psid, `CAHA xin chào 👋 Anh/chị vừa nhắn: "${ev.message.text}"`);
          } else if (ev.postback?.payload) {
            sendText(psid, `Anh/chị vừa bấm: ${ev.postback.payload}`);
          }
        }
      }
    } catch (e) {
      console.error("Handler error:", e?.response?.data || e);
    }
  }

  if (req.method === "OPTIONS") {
    return res.status(200).end();
  }
};

// Hàm gọi Send API
async function sendText(psid, text) {
  if (!PAGE_TOKEN) {
    console.warn("⚠️ PAGE_ACCESS_TOKEN chưa có trên Vercel");
    return;
  }
  const url = `https://graph.facebook.com/v23.0/me/messages?access_token=${PAGE_TOKEN}`;
  await axios.post(url, {
    recipient: { id: psid },
    message: { text }
  });
}
