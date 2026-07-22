// api/chat.js
// d-more.jp (有限会社ホームエレガンス / ダスキンサービスマスターモアー店) 用
// AIチャットウィジェット バックエンド (Vercel Serverless Function)

export default async function handler(req, res) {
    // CORS対応（d-more.jp からの呼び出しを許可）
  res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') {
        return res.status(200).end();
  }

  if (req.method !== 'POST') {
        return res.status(405).json({ error: 'Method Not Allowed' });
  }

  const { messages } = req.body || {};

  if (!Array.isArray(messages) || messages.length === 0) {
        return res.status(400).json({ error: 'messages is required (array)' });
  }

  // 直近の会話のみ送信（トークン節約・簡易的な会話履歴制限）
  const recentMessages = messages.slice(-10);

  const systemPrompt = `あなたは「ダスキンサービスマスターモアー店（有限会社ホームエレガンス）」の
  公式サイト d-more.jp に設置されたチャット案内スタッフです。
  杉並区・中野区・練馬区を中心にハウスクリーニング（エアコン、浴室、キッチン、窓、レンジフード等）を提供しています。

  【対応方針】
  - 丁寧で親しみやすい、簡潔な日本語で回答する（1〜4文程度を目安に）。
  - サービス内容、対応エリア、予約・見積もりの流れ、当日の準備などの一般的な質問には分かりやすく回答する。
  - 正確な料金・日程・在庫状況など、サイト側で確定できない情報は断定せず、
    「正確な金額はお見積もりが必要です」「お電話またはお問い合わせフォームでご確認ください」と案内する。
    - 個人情報（氏名・住所・電話番号等）の聞き取りは行わない。予約や見積もり依頼は
      公式のお問い合わせフォームまたは電話への誘導に留める。
      - サービス対象外の質問（他社比較の誹謗中傷、無関係な雑談等）には丁寧に本題へ誘導する。
      - わからないことは正直に「わかりかねますので、お電話またはお問い合わせフォームからご確認ください」と答える。`;

  try {
        const apiKey = process.env.OPENAI_API_KEY;
        if (!apiKey) {
                return res.status(500).json({ error: 'OPENAI_API_KEY is not configured' });
        }

      const response = await fetch('https://api.openai.com/v1/chat/completions', {
              method: 'POST',
              headers: {
                        'Content-Type': 'application/json',
                        Authorization: `Bearer ${apiKey}`,
              },
              body: JSON.stringify({
                        model: 'gpt-4o-mini',
                        messages: [{ role: 'system', content: systemPrompt }, ...recentMessages],
                        temperature: 0.4,
                        max_tokens: 500,
              }),
      });

      if (!response.ok) {
              const errText = await response.text();
              console.error('OpenAI API error:', response.status, errText);
              return res.status(502).json({ error: 'Upstream API error' });
      }

      const data = await response.json();
        const reply = data.choices?.[0]?.message?.content ?? '';

      return res.status(200).json({ reply });
  } catch (err) {
        console.error('chat.js error:', err);
        return res.status(500).json({ error: 'Internal Server Error' });
  }
}
