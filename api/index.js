export const config = {
  api: {
    bodyParser: false, // 🔧 bắt buộc để tự parse JSON đúng cho Lark
  },
};

export default async function handler(req, res) {
  try {
    if (req.method !== "POST") {
      return res.status(405).json({ error: "Method not allowed" });
    }

    // Lark gửi raw JSON, ta phải parse thủ công
    let body = "";
    req.on("data", chunk => {
      body += chunk;
    });

    req.on("end", () => {
      try {
        const json = JSON.parse(body);

        // ✅ Khi Lark gửi challenge
        if (json && json.challenge) {
          console.log("✅ Challenge received:", json.challenge);
          return res
            .status(200)
            .json({ challenge: json.challenge });
        }

        // ✅ Khi có event thật
        console.log("📦 Event received:", json);
        return res
          .status(200)
          .json({ code: 0, msg: "success" });
      } catch (e) {
        console.error("❌ JSON parse error:", e);
        return res
          .status(400)
          .json({ error: "Invalid JSON" });
      }
    });
  } catch (err) {
    console.error("❌ Error:", err);
    return res
      .status(500)
      .json({ error: err.message });
  }
}
