const faunadb = require('faunadb');
const q = faunadb.query;

const client = new faunadb.Client({
    secret: process.env.FAUNA_SECRET
});

exports.handler = async (event) => {
    const { score } = JSON.parse(event.body);
    try {
        await client.query(
            q.Create(q.Collection('scores'), { data: { score, date: q.Now() } })
        );
        const scores = await client.query(
            q.Map(
                q.Paginate(q.Documents(q.Collection('scores')), { size: 10 }),
                q.Lambda('X', q.Get(q.Var('X')))
            )
        );
        const rank = (await client.query(
            q.Count(q.Match(q.Index('scores_by_score'), q.GT(parseInt(score))))
        )) + 1;
        return {
            statusCode: 200,
            body: JSON.stringify({
                scores: scores.data.map(d => ({ score: d.data.score })),
                rank: rank > 999 ? '999+' : rank
            })
        };
    } catch (error) {
        return {
            statusCode: 500,
            body: JSON.stringify({ error: 'Failed to process score' })
        };
    }
};
