// api/debug-token.js
// Xác minh Facebook access token ở phía server (Vercel Function)

const APP_ID = "1181036240494792";                 // App ID của anh
const APP_SECRET = process.env.FB_APP_SECRET || ""; // Đặt trong Vercel → Settings → Environment Variables

module.exports = async (req, res) => {
  try {
    // Lấy access_token từ query: /api/debug-token?access_token=EA...
    const accessToken = (req.query?.access_token || "").trim();
    if (!APP_SECRET) {
      return res.status(500).json({ ok: false, error: "missing_FB_APP_SECRET_env" });
    }
    if (!accessToken) {
      return res.status(400).json({ ok: false, error: "missing_access_token" });
    }

    // 1) Gọi debug_token để xác minh token thuộc app & còn hiệu lực
    const appToken = `${APP_ID}|${APP_SECRET}`;
    const dbgURL = `https://graph.facebook.com/v23.0/debug_token?input_token=${encodeURIComponent(
      accessToken
    )}&access_token=${encodeURIComponent(appToken)}`;

    const dbgRes = await fetch(dbgURL);
    const dbgJson = await dbgRes.json();

    const d = dbgJson?.data;
    if (!d || !d.is_valid || String(d.app_id) !== String(APP_ID)) {
      return res.status(401).json({ ok: false, error: "invalid_token", debug: dbgJson });
    }

    // 2) (Tuỳ chọn) Lấy profile tối thiểu từ Graph bằng user token
    const meURL = `https://graph.facebook.com/v23.0/me?fields=id,name,email,picture&access_token=${encodeURIComponent(
      accessToken
    )}`;
    const meRes = await fetch(meURL);
    const profile = await meRes.json();

    // 3) Trả kết quả
    return res.status(200).json({
      ok: true,
      profile,                 // { id, name, email?, picture }
      scopes: d.scopes || [],  // các quyền mà token có
      expires_at: d.expires_at // timestamp hết hạn token (epoch seconds)
    });
  } catch (err) {
    console.error("[debug-token] error:", err);
    return res.status(500).json({ ok: false, error: "server_error" });
  }
};
