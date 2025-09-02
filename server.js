const express = require("express");
const bodyParser = require("body-parser");

const app = express();
app.use(bodyParser.json());

// Xác minh webhook với Facebook
app.get("/webhook", (req, res) => {
  const VERIFY_TOKEN = "caha_verify_token"; // anh có thể đổi thành token bí mật của riêng mình
  const mode = req.query["hub.mode"];
  const token = req.query["hub.verify_token"];
  const challenge = req.query["hub.challenge"];

  if (mode && token) {
    if (mode === "subscribe" && token === VERIFY_TOKEN) {
      console.log("Webhook verified!");
      res.status(200).send(challenge);
    } else {
      res.sendStatus(403);
    }
  }
});

// Nhận event từ Facebook
app.post("/webhook", (req, res) => {
  console.log("Sự kiện nhận được:", JSON.stringify(req.body, null, 2));
  res.status(200).send("EVENT_RECEIVED");
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Webhook server đang chạy cổng ${PORT}`));
