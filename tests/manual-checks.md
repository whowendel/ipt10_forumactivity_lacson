# Manual checks (lab step 8)

`php tests/smoke.php` runs these automatically. Do them by hand as well for the
screenshots in your Google Doc. Start from a fresh import of `database/schema.sql`.

| # | Do this | Expect |
|---|---------|--------|
| 1 | Open `/discussions/1` while logged out | Tree with depths 0 to 4, authors, UTC times, no reply forms |
| 2 | Register at `/register`, then log in at `/login` | "Account created", then "Welcome back" and your name in the header |
| 3 | Open Reply under renz's depth-4 comment and post | New comment labelled **Depth 5** with your username |
| 4 | Use "Add a comment" at the bottom | New comment labelled **Depth 0** with your username |
| 5 | Post `<script>alert(1)</script> <b>hi</b>` as a comment | The text shows literally; no alert, no bold |
| 6 | Log in with a wrong password | 422, "Incorrect username or password." |
| 7 | Log in as seed user `mika` with any password | 422, same message (NULL hash never authenticates) |
| 8 | Open `/discussions/999` | 404 Not Found |
| 9 | Open `/discussions/abc` | 400 Bad Request |
| 10 | In browser dev tools, change the hidden `csrf_token` value, then post | 403 Forbidden, no new comment |
| 11 | Log out, then run the guest POST below | 303 redirect to `/login`, no new comment |
| 12 | Logged in, run the cross-discussion POST below | 422, no new comment |

Guest POST (check 11), no cookie at all:

    curl -i -X POST -d "body=guest post" http://localhost:8000/discussions/1/comments

Cross-discussion parent (check 12): comment 5 belongs to discussion 1, the address says discussion 2.
In dev tools, edit a reply form's `action` on `/discussions/2` to
`/discussions/2/comments/5/replies` and submit it.

Confirm "no new comment" with:

    SELECT COUNT(*) FROM comments;

Optional challenge: click "Hide replies" on renz's first comment (tala, jun, bea's replies
disappear; bea's top-level comment stays), click again to reopen, repeat with the keyboard
(Tab, Enter), then disable JavaScript and confirm every reply is visible and no buttons appear.
