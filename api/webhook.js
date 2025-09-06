// api/webhook.js
const VERIFY_TOKEN = process.env.VERIFY_TOKEN || "cauhai_verify_123";
const PAGE_TOKEN   = process.env.PAGE_TOKEN; // lấy trong App → Messenger → Token

module.exports = async (req, res) => {
  // [A] Facebook VERIFY (GET)
  if (req.method === "GET") {
    const mode      = req.query["hub.mode"];
    const token     = req.query["hub.verify_token"];
    const challenge = req.query["hub.challenge"];
    if (mode === "subscribe" && token === VERIFY_TOKEN) return res.status(200).send(challenge);
    return res.status(403).send("Forbidden");
  }

  // [B] Nhận SỰ KIỆN (POST)
  if (req.method === "POST") {
    const body = req.body;
    if (body.object === "page") {
      for (const entry of body.entry) {
        for (const event of entry.messaging || []) {
          const psid = event?.sender?.id;
          const text = event?.message?.text;
          const postback = event?.postback;
          if (psid && PAGE_TOKEN) {
            if (postback?.payload === "GET_STARTED") {
              await sendText(psid, "Chào mừng đến Cậu Hai Home Decor ✨ Gõ 'menu' để xem gợi ý.");
            } else if (text) {
              const t = text.trim().toLowerCase();
              if (t === "menu") {
                await sendText(psid, "Menu:\n- Trang trí: https://cauhaihomedecor.vn/collections/trang-tri\n- Gối/Chăn: https://cauhaihomedecor.vn/collections/vo-goi-sofa\n- Bếp/Bàn ăn: https://cauhaihomedecor.vn/collections/nha-bep-ban-an");
              } else {
                await sendText(psid, `Bạn vừa nói: ${text}`);
              }
            }
          }
        }
      }
      return res.status(200).send("EVENT_RECEIVED");
    }
    return res.sendStatus(404);
  }

  return res.status(405).send("Method Not Allowed");

  async function sendText(psid, text) {
    const url = `https://graph.facebook.com/v23.0/me/messages?access_token=${PAGE_TOKEN}`;
    await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ recipient: { id: psid }, message: { text } })
    });
  }
};
