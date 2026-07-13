# HopeUI Tailwind page catalog

All paths relative to `hopa/hopeui-pro-tailwind-v3.1.0/hopeui-pro-tailwind/html/`.
The "nuxnan use" column suggests which nuxnan feature each page's design fits best.

## Social app (`social-app/`) — best match for nuxnan's feed/social features

| Page | Contents | nuxnan use |
|---|---|---|
| `social-app/index.html`, `newsfeed.html` | post feed, stories bar, create-post box, post cards (like/comment/share) | social feed (`stores/feed`, `components/share/`) |
| `social-app/social-profile.html`, `friend-profile.html` | profile cover, tabs, about card, photo grid | user/academy profile pages |
| `social-app/friend-list.html`, `friend-request.html` | member grids, request accept/decline cards | followers, academy members |
| `social-app/group-list.html`, `group-detail.html` | group cards, group cover + feed | academy list / academy detail |
| `social-app/event-list.html`, `event-detail.html`, `birthday.html` | event cards, RSVP | school events, calendar |
| `social-app/notification.html` | notification list rows | notifications page |
| `social-app/account-setting.html` | settings form tabs | user settings |
| `social-app/profile-badges.html` | badge/achievement grid | gamification badges |
| `social-app/profile-images.html`, `profile-video.html` | media grids | media galleries |

## E-commerce (`e-commerce/`) — best match for Earn/Marketplace

| Page | Contents | nuxnan use |
|---|---|---|
| `e-commerce/index.html`, `vendor-dashboard.html` | sales dashboard, stat cards, charts | marketplace/seller dashboard |
| `product-grid.html`, `product-list.html`, `shop-main.html`, `shop-left-filter.html`, `shop-right-filter.html` | product cards, filters sidebar, sorting | marketplace browse |
| `product-detail.html` (+ `-360`, `-3d`) | gallery, price, variants, reviews | marketplace item detail |
| `wishlist.html`, `order-process.html`, `invoice.html` | wishlist grid, checkout steps, invoice layout | wallet/orders |
| `categories-list.html`, `user-list.html`, `user-profile.html` | admin tables + profile | marketplace admin |

## Chat & Mail

| Page | Contents | nuxnan use |
|---|---|---|
| `chat/index.html` | 3-pane chat: conversation list, message thread, contact info | realtime chat (Reverb) |
| `mail/index.html`, `mail/email-compose.html` | inbox list, reading pane, compose form | messages/announcements |

## File manager (`file-manager/`)

`index.html`, `all-files.html`, `document-folder.html`, `image-folder.html`, `video-folder.html`,
`trash.html` — folder grids, file tables, storage stats sidebar. → course materials, user files.

## Blog (`blog/`) — best match for academy posts/articles

`blog-main.html`, `blog-grid.html`, `blog-list.html`, `blog-details.html` (article + comments),
`blog-category.html`, `blog-comments.html`, `blog-trending.html`, `index.html`.
→ academy posts, lesson articles, comments UI.

## Appointment (`appointment/`)

`index.html` (landing), `book-appointment.html` (booking form + time slots), `doctor-visit.html`.
→ tutoring/consultation booking, teacher office hours.

## Dashboard core (`dashboard/`)

| Page | Contents | nuxnan use |
|---|---|---|
| `dashboard/index.html` | main admin dashboard: stat cards, ApexCharts, activity feed | Dashboard.vue redesign |
| `admin.html`, `alternate-dashboard.html` | admin variants | admin views |
| `index-horizontal.html`, `index-dual-*.html`, `index-boxed*.html` | alternative nav layouts | layout experiments only |
| `app/user-list.html`, `app/user-add.html`, `app/user-profile.html`, `app/user-privacy-setting.html` | user CRUD screens | student/teacher management |
| `special-pages/kanban.html` | kanban board | assignment board |
| `special-pages/calender.html` | FullCalendar page | class schedule |
| `special-pages/timeline.html` | vertical timeline | course progress, activity history |
| `special-pages/pricing.html` | pricing tier cards | subscription/packages |
| `special-pages/billing.html` | billing/payment form | wallet top-up |
| `widget/widgetbasic.html`, `widgetcard.html`, `widgetchart.html` | reusable widget/card/chart snippets | any dashboard widget — check here first |
| `table/basic-table.html`, `bordered-table.html`, `fancy-table.html`, `data-table.html` | table styles | gradebook, rosters, reports |
| `form/form-element.html`, `form-validation.html`, `form-wizard.html` | all input styles, validation states, multi-step wizard | any form; wizard → course creation flow |
| `auth/sign-in.html`, `sign-up.html`, `recoverpw.html`, `confirm-mail.html`, `lock-screen.html` | auth screens (simple) | login/register redesign |
| `auth-pro/*` (7 pages incl. `two-factor.html`, `account-deactivate.html`) | fancier split-layout auth | login/register redesign |
| `errors/error404.html`, `error500.html`, `maintenance.html` | error pages with artwork | error pages |
| `extra/privacy-policy.html`, `terms-of-use.html` | legal text layout | policy pages |
| `icon/*`, `map/*`, `plugins/*` | icon sets, maps, plugin demos (charts, quill, uppy, sweetalert, flatpickr, tippy…) | reference for plugin usage patterns |
| `blank-page.html` | empty shell | shows the minimal page skeleton |

## Tips

- Widget/card designs are duplicated across dashboards — `dashboard/widget/*.html` is the most
  concentrated place to lift individual card patterns without wading through a full page.
- The same module exists in the Vue variant under
  `hopa/hopeui-pro-vue-4.1.0/code-vue/src/views/modules/<module>/` — open the matching folder to
  see how Iqonic split the page into components (structure only; its markup is Bootstrap).
