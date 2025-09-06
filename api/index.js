// GET https://<project>.vercel.app/api
module.exports = (req, res) => {
  res.status(200).send("CAUHAI Webhook is running (Vercel Functions)");
};
