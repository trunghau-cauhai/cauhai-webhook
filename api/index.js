// GET https://<project>.vercel.app/api
module.exports = (req, res) => {
  res.status(200).send("CAHA Webhook is running (Vercel Functions)");
};
