const express = require("express");
const bodyParser = require("body-parser");
const axios = require("axios");

const app = express();
app.use(bodyParser.json());

// Lấy token từ Environment Variables trên Vercel
const VERIFY_TOKEN = process.env.VERIFY_TOKEN || "caha_verify_token";
const PAGE_TOKEN   = process.env.PAGE_ACCESS_TOKEN || "";

// Trang kiểm tra nhanh
app.get("/", (_req, res) => res.send("CAHA Webhook is running"));

/**
 * 1) VERIFY: Facebook gọi GET /webhook để xác minh
 */
app.get("/webhook", (req, res) => {
  const mode = req.query["hub.mode"];
  const token = req.query["hub.verify_token"];
  const challenge = req.query["hub.challenge"];

  if (mode === "subscribe" && token === VERIFY_TOKEN) {
    console.log("✅ Verified webhook");
    return res.status(200).send(challenge);
  }
  return res.sendStatus(403);
});

/**
 * 2) RECEIVE: Facebook gửi POST /webhook khi có tin nhắn/sự kiện
 */
app.post("/webhook", async (req, res) => {
  res.status(200).send("EVENT_RECEIVED"); // báo đã nhận

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

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`🚀 Webhook server chạy cổng ${PORT}`));
