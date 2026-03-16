# Integration Plan: MI-AUTO WordPress Theme

## Theme
- Name: MI-AUTO
- Slug: miauto
- Prefix: miauto_

## Pages (total: 9)
| Page | Template | Status |
|------|----------|--------|
| Главная | front-page.php | Done |
| О нас | page-about.php | Done |
| Услуги | page-services.php | Done |
| Карточка услуги | single-miauto_service.php | Done |
| Цены | page-prices.php | Done |
| Наши работы | page-works.php | Done |
| Блог | home.php | Done |
| Статья | single.php | Done |
| Контакты | page-contacts.php | Done |

## Sections (homepage)
| Section | Template | CSS | JS | Status |
|---------|----------|-----|-----|--------|
| Top Bar | top-bar/top-bar.php | top-bar.css | top-bar.js | Done |
| Header | header.php | header/header.css | header/header.js | Done |
| Hero | hero/hero.php | hero.css | hero.js | Done |
| Car Models | car-models/car-models.php | car-models.css | — | Done |
| Services | services/services.php | services.css | services.js | Done |
| About + Articles | about/about.php | about.css | — | Done |
| Partners | partners/partners.php | partners.css | — | Done |
| Svc Details | svc-details/svc-details.php | svc-details.css | svc-details.js | Done |
| Contacts | contacts/contacts.php | contacts.css | — | Done |
| Form Section | form-section/form-section.php | form-section.css | — | Done |
| Footer | footer.php | footer/footer.css | footer/footer.js | Done |
| Scroll Top | footer.php (inline) | scroll-top.css | scroll-top.js | Done |

## Sections (about page)
| Section | Template | CSS | JS | Status |
|---------|----------|-----|-----|--------|
| Breadcrumbs | breadcrumbs/breadcrumbs.php | breadcrumbs.css | — | Done |
| About Hero | about-hero/about-hero.php | about-hero.css | — | Done |
| About Intro | about-intro/about-intro.php | about-intro.css | — | Done |
| Work Process | work-process/work-process.php | work-process.css | — | Done |
| Advantages | advantages/advantages.php | advantages.css | — | Done |
| Partners | partners/partners.php | partners.css | — | Reused |
| Contacts | contacts/contacts.php | contacts.css | — | Reused |
| Form Section | form-section/form-section.php | form-section.css | — | Reused |

## Sections (other pages)
| Section | Template | CSS | JS | Status |
|---------|----------|-----|-----|--------|
| Works | works/works.php | works.css | works.js | Done |
| Prices | prices/prices.php | prices.css | prices.js | Done |
| Blog | blog/blog.php | blog.css | blog.js | Done |
| Article | article/article.php | article.css | — | Done |
| Service Card | service-card/service-card.php | service-card.css | service-card.js | Done |

## Registered Assets (handles)
### Styles (22)
miauto-base, miauto-top-bar, miauto-header, miauto-hero, miauto-car-models, miauto-services, miauto-about, miauto-partners, miauto-svc-details, miauto-contacts, miauto-form, miauto-footer, miauto-scroll-top, miauto-breadcrumbs, miauto-about-hero, miauto-about-intro, miauto-work-process, miauto-advantages, miauto-works, miauto-prices, miauto-blog, miauto-article, miauto-service-card

### Scripts (11)
miauto-top-bar, miauto-header, miauto-hero, miauto-services, miauto-svc-details, miauto-footer, miauto-scroll-top, miauto-works, miauto-prices, miauto-blog, miauto-service-card

## CPT
| CPT | Slug | Status |
|-----|------|--------|
| Бренды | miauto_brand | Registered |
| Модели авто | miauto_model | Registered |
| Услуги | miauto_service | Registered |
| Работы | miauto_work | Registered |
| Партнёры | miauto_partner | Registered |

## Carbon Fields Containers
- Theme Options: Top Bar, Header, Contacts, Rating, Footer, Form
- Post Meta (front page): Hero slides, Homepage sections (services, contacts)
- Post Meta (about page): About Hero, About Intro, Work Process, Advantages
- Post Meta (contacts page): Contacts section title, decoration, map
- Post Meta (prices page): Prices title, subtitle, models with nested categories/rows
- Post Meta (miauto_work CPT): Work details (model, mileage, issue, defects, done, price, duration, gallery)
- Post Meta (miauto_service CPT): SC Hero, Symptoms, Svc List, SC Prices, Warranty, Service Price

## Checklist
- [x] style.css contains valid theme header
- [x] functions.php includes all inc/ files
- [x] All assets registered in inc/enqueue/enqueue.php
- [x] Section styles enqueued conditionally in section templates
- [x] Section scripts enqueued conditionally in section templates
- [x] All output escaped (esc_html, esc_url, esc_attr, wp_kses_post)
- [x] Each section template has early return on missing data
- [x] Carbon Fields defined for each section
- [x] miauto_highlight_title() helper available
- [x] PHPDoc on all functions
- [x] _PENDING_DELETIONS.md created
- [x] All 9 pages integrated
