// api/webhook.js
const VERIFY_TOKEN = process.env.VERIFY_TOKEN || "cauhai_verify_123";
const PAGE_TOKEN   = process.env.PAGE_TOKEN;   // Page Access Token
const FLOW_URL     = process.env.FLOW_URL;     // Webhook Flow (CAUHAIMESSENGER)
const FLOW_TOKEN   = process.env.FLOW_TOKEN;   // Static Token Flow

module.exports = async (req, res) => {
  // [A] Facebook gọi GET để verify
  if (req.method === "GET") {
    const mode      = req.query["hub.mode"];
    const token     = req.query["hub.verify_token"];
    const challenge = req.query["hub.challenge"];

    if (mode === "subscribe" && token === VERIFY_TOKEN) {
      return res.status(200).send(challenge);
    }
    return res.status(403).send("Forbidden");
  }

  // [B] Facebook gửi POST khi có sự kiện
  if (req.method === "POST") {
    const body = req.body;

    if (body.object === "page") {
      for (const entry of body.entry) {
        for (const event of entry.messaging || []) {
          const psid    = event?.sender?.id;
          const textMsg = event?.message?.text;
          const postback = event?.postback;

          // Forward sang Flow (nếu có)
          if (FLOW_URL && FLOW_TOKEN) {
            await fetch(FLOW_URL, {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                "X-Api-Key": FLOW_TOKEN
              },
              body: JSON.stringify({ psid, text: textMsg, postback, raw: event })
            });
          }

          // Trả lời cơ bản (demo)
          if (PAGE_TOKEN && psid) {
            if (postback?.payload === "GET_STARTED") {
              await sendText(psid, "Chào mừng bạn đến Cậu Hai Home Decor ✨");
            } else if (textMsg) {
              await sendText(psid, `Bạn vừa nói: ${textMsg}`);
            }
          }
        }
      }
      return res.status(200).send("EVENT_RECEIVED");
    }

    return res.sendStatus(404);
  }

  return res.status(405).send("Method Not Allowed");
};

// --- Helper: gửi text qua Send API ---
async function sendText(psid, text) {
  const url = `https://graph.facebook.com/v23.0/me/messages?access_token=${PAGE_TOKEN}`;
  await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      recipient: { id: psid },
      message: { text }
    })
  });
}
