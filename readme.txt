FUKING PLAN

- UPDATE


HOMEPAGE QUERY (NOT FINISHED YET)

SELECT posts.id, posts.author_id,posts.post_content,null'reacted_by', null'shared_by', posts.created_at'at' 
FROM posts
JOIN followers
ON followers.followed_by_id = 1 AND posts.author_id = followers.user_id
UNION
SELECT posts.id'id',posts.author_id, posts.post_content'post_content', null'reacted_by', reactions.user_id'shared_by', reactions.created_at'at' 
FROM posts 
JOIN followers
ON followers.followed_by_id = 1
JOIN reactions 
ON reactions.post_id = posts.id 
AND reactions.user_id = followers.user_id
UNION ALL 
SELECT posts.id'id',posts.author_id, posts.post_content'post_content', null'reacted_by', shared_posts.user_id'shared_by', shared_posts.created_at'at' 
FROM posts 
JOIN followers
ON followers.followed_by_id = 1
JOIN shared_posts 
ON shared_posts.post_id = posts.id 
AND shared_posts.user_id = followers.user_id 
AND TIMEDIFF(CURRENT_TIMESTAMP, shared_posts.created_at) <= TIMEDIFF('24:00:00', '0:0:0')

TRENDING HASHTAG QUERY

SELECT hashtag_name, COUNT(*)'total_posts' FROM hashtags WHERE DATEDIFF(CURRENT_TIMESTAMP, created_at) <= 7 GROUP BY hashtag_name ORDER BY total_posts
