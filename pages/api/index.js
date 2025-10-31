import * as lark from '@larksuiteoapi/node-sdk';
import { Redis } from '@upstash/redis';

const dispatcher = new lark.EventDispatcher({
  encryptKey: process.env.LARK_ENCRYPT_KEY,
  verificationToken: process.env.LARK_VERIFICATION_TOKEN,
});

const redis = new Redis({
  url: process.env.UPSTASH_REDIS_REST_URL,
  token: process.env.UPSTASH_REDIS_REST_TOKEN,
});

dispatcher.registerEvent('im.message.receive_v1', async (data) => {
  const ev = data?.event ?? {};
  const message = {
    chatId: ev?.message?.chat_id,
    msgType: ev?.message?.message_type,
    content: ev?.message?.content,
    sender: ev?.sender?.sender_id,
    ts: ev?.message?.create_time,
  };
  console.log('[LARK] New message:', message);
  try { await redis.lpush('lark:messages', JSON.stringify(message)); } catch {}
});

dispatcher.registerEvent('contact.user.updated_v3', async (data) => {
  const ev = data?.event ?? {};
  try { await redis.lpush('lark:events', JSON.stringify({ type: 'contact.user.updated_v3', ev })); } catch {}
});

export default async function handler(req, res) {
  if (req.method === 'GET') return res.status(200).json({ ok: true, ping: 'lark-webhook-alive' });
  if (req.method !== 'POST') return res.status(405).json({ error: 'Method not allowed' });
  try { await dispatcher.dispatch(req, res); if (!res.headersSent) res.status(200).json({ ok: true }); }
  catch (e) { if (!res.headersSent) res.status(200).json({ ok: false, error: 'dispatch_error' }); }
}
