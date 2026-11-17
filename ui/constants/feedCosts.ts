// Point costs for the PUBLIC newsfeed (regular posts on /api/posts...).
// Mirror of api/nuxnanravel/config/economy.php feed_costs — change both together.
// Course posts / academy posts / shares / comment-likes keep their own (old) costs.
export const FEED_COSTS = {
  post: 480,
  comment: 240,
  commentAuthorShare: 120,
  reply: 300,
  replyAuthorShare: 150,
  like: 120,
  likeAuthorShare: 60,
  unlike: 60,
  dislike: 120,
  dislikeAuthorFine: 60,
  undislike: 60,
} as const
