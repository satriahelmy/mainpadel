# MainPadel --- Design Specification

**Version:** 1.0\
**Status:** Draft\
**Product:** MainPadel\
**Platform:** Responsive Web Application\
**Primary Device:** Smartphone

## 1. Design Goal

MainPadel adalah utility untuk mengelola sesi padel secara cepat ketika
user sedang berada di court.

Desain harus terasa fast, sporty, clean, lightweight, friendly, dan
mobile-first.

MainPadel bukan enterprise dashboard, tournament management software
yang kompleks, social network, AI application, atau generic Laravel
admin panel.

> **Open → Draw → Play → Score → Next.**

User harus dapat memahami apa yang perlu dilakukan berikutnya tanpa
membaca instruksi panjang.

## 2. Usage Context

MainPadel kemungkinan besar digunakan melalui smartphone, sambil berdiri
di pinggir court, di antara pertandingan, dengan perhatian user yang
terbatas, terkadang di bawah cahaya outdoor, dan dengan satu orang
bertindak sebagai organizer.

Konsekuensinya:

-   Button harus besar.
-   Text penting harus mudah dibaca.
-   Score entry harus sangat cepat.
-   Current match harus menjadi visual focus.
-   Navigation harus sederhana.
-   Informasi sekunder tidak boleh memenuhi layar.
-   Desktop tetap didukung, tetapi bukan prioritas utama.

## 3. Visual Direction

Visual direction: **Modern recreational sports app.**

Hindari corporate dashboard, glassmorphism berlebihan, neon cyberpunk,
gradient di setiap komponen, card di dalam card, excessive rounded
containers, excessive shadows, dan generic AI-generated SaaS landing
page.

Gunakan whitespace sebagai bagian utama layout. Interface harus terasa
intentional dan sederhana.

## 4. Brand

Product name: **MainPadel**

Wordmark dapat berupa `MAINPADEL` atau `MainPadel`.

Logo kompleks belum diperlukan untuk MVP. Jika membutuhkan brand mark,
gunakan simbol geometris sederhana yang terinspirasi dari padel court,
racket, ball, atau rotation/draw. Jangan gunakan ilustrasi racket + ball
generik sebagai logo utama jika tidak diperlukan.

## 5. Color System

Gunakan palette yang sporty tetapi restrained.

Recommended direction:

-   Background: `#F7F7F4`
-   Surface: `#FFFFFF`
-   Text Primary: `#111111`
-   Text Secondary: `#6B6B67`
-   Border: `#E5E5E0`
-   Accent: `#C7F000`
-   Dark: `#151715`

Exact colors dapat dituning saat implementation.

Accent lime digunakan secara terbatas untuk primary CTA, active state,
score highlight, current round, dan winner indication. Jangan membuat
seluruh UI berwarna hijau.

## 6. Typography

Gunakan sans-serif modern.

Preferred: **Inter**

Fallback:

`system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif`

Hierarchy:

-   Page title: 28--32px / semibold-bold
-   Round number: 12--14px / uppercase / medium
-   Player names: 18--22px / semibold
-   Score: 40--56px / bold
-   Body: 15--16px
-   Metadata: 13--14px

Score harus menjadi salah satu elemen paling mudah dibaca di interface.

## 7. Layout

Mobile layout menggunakan primary content width sekitar 480--640px.

Desktop tidak perlu berubah menjadi dashboard tiga kolom. Center
application content dan berikan whitespace. Beberapa screen seperti
standings dapat menggunakan width lebih besar jika dibutuhkan.

## 8. Navigation

Saat session sedang berlangsung, gunakan simple bottom navigation:

**Play · Rounds · Standings · Players**

Icons optional. Label tetap ditampilkan agar navigation tidak bergantung
pada interpretasi icon.

Bottom navigation fixed pada mobile, tidak terlalu tinggi, safe-area
aware, dan active state jelas. Tidak perlu hamburger menu untuk core
game experience.

## 9. Home Screen

Tujuan home adalah memulai game secepat mungkin.

Contoh:

    MainPadel

    Your padel games,
    without the spreadsheet.

    [ + New Game ]

    Recent Games

    Friday Padel
    15 Sep · 6 players · Completed

Home tidak perlu analytics, chart, promotional banners, multiple CTAs,
atau dashboard statistics.

Primary action: **New Game**.

## 10. Create Game

Gunakan single-page form sederhana.

    New Game

    Game name
    [ Friday Padel            ]

    Players
    [ Helmy                 × ]
    [ Andi                  × ]
    [ Budi                  × ]
    [ Rizky                 × ]

    [ + Add player ]

    Courts
    [ − ]     1     [ + ]

    Points per match
    [ 15 ] [ 21 ] [ Custom ]

    Rounds
    (•) Auto
    ( ) Custom

    [ Generate Draw ]

Player input harus memungkinkan Enter untuk langsung menambah pemain
berikutnya, remove dengan satu tap, dan input cepat tanpa modal
berulang.

Default target: **21 points**.

## 11. Validation

Validation harus contextual.

Untuk 6 players · 2 courts, tampilkan:

> 6 players can fill 1 court per round.

Jangan tampilkan sebagai destructive red error karena kondisi tersebut
valid.

Jika kurang dari 4 pemain:

> Add at least 4 players to start.

Disable **Generate Draw**. Gunakan red hanya untuk actual errors.

## 12. Drawing Preview

Setelah Generate Draw:

    Friday Padel

    6 players
    1 court
    21 points

    Drawing ready ✓

    ROUND 1

    COURT 1

    Helmy + Andi
         VS
    Budi + Rizky

    RESTING
    Fajar · Dimas

    [ Start Game ]

Secondary action: **View all rounds**.

Jangan tampilkan semua round sekaligus pada primary screen. Organizer
terutama perlu tahu: **Siapa yang main sekarang?**

## 13. Play Screen

Ini adalah screen terpenting MainPadel.

Hierarchy:

**Round indicator → Court → Teams → Score/Input Score → Resting players
→ Next action**

Jika multiple courts, semua court berada dalam vertical flow.

## 14. Match Card

Match card merupakan core reusable component.

    ┌──────────────────────────────┐
    │ COURT 1                      │
    │                              │
    │ Helmy + Andi                 │
    │                              │
    │             VS               │
    │                              │
    │ Budi + Rizky                 │
    │                              │
    │        [ Enter Result ]      │
    └──────────────────────────────┘

Gunakan border ringan. Hindari large shadows, gradients, oversized
radius, decorative icons, dan excessive badges.

Border radius sekitar 12--16px.

## 15. Score Entry

Score entry harus bisa dilakukan dalam beberapa detik.

Karena target total diketahui, jika target = 21 dan user memasukkan Team
A = 12, sistem boleh otomatis mengisi Team B = 9.

Flow ideal:

**Tap Enter Result → type 12 → opponent automatically becomes 9 → Save**

Direct numeric input tetap didukung.

## 16. Score Validation

Jika Team A = 13 dan Team B = 9, total menjadi 22/21.

Tampilkan inline:

> Scores must total 21 points.

**Save Result** disabled. Tidak perlu browser alert.

## 17. Completed Match

Setelah score disimpan, tampilkan status `FINAL`, kedua skor, dan winner
dengan stronger font atau subtle accent indicator.

Jangan membuat losing team terlalu faded sampai sulit dibaca.

## 18. Round Completion

Jika seluruh court pada current round selesai:

    Round 3 complete ✓

    [ Continue to Round 4 ]

Button menjadi primary CTA.

Jangan otomatis berpindah round segera setelah score terakhir
dimasukkan. Organizer harus dapat melihat hasil sebentar sebelum
melanjutkan.

## 19. Standings

Standings merupakan second-most-important screen.

Mobile:

    STANDINGS

    1   Helmy
        58 pts
        5 played · +12

    2   Andi
        55 pts
        5 played · +7

Default information:

-   Rank
-   Player
-   Points
-   Played
-   +/-

Desktop dapat menggunakan:

`Rank | Player | P | W | L | PF | PA | +/-`

## 20. Ranking Highlight

Top 3 boleh memiliki visual distinction ringan. Tidak perlu podium
graphic atau medal illustration besar.

Gunakan **LIVE STANDINGS** jika session masih berlangsung dan **FINAL
STANDINGS** jika selesai.

## 21. Rounds Screen

Rounds screen digunakan untuk melihat keseluruhan drawing dan membedakan
state Completed, Current, dan Upcoming dengan jelas tanpa terlalu banyak
warna.

## 22. Players Screen

Tampilkan pemain aktif beserta jumlah match dan rest.

    PLAYERS

    6 active

    Helmy
    5 matches · 1 rest

    Andi
    5 matches · 1 rest

    [ + Add Player ]

Screen ini juga menjadi tempat late join, withdraw player, dan
participation count.

## 23. Add Player During Game

Gunakan bottom sheet atau compact modal:

    Add player

    Name
    [ Galih                  ]

    Galih will join from Round 4.
    Future rounds will be redrawn.

    [ Add & Redraw ]
    [ Cancel ]

Jangan mengubah completed rounds.

## 24. Player Leave

Saat user memilih Stop Playing, tampilkan confirmation yang menjelaskan
bahwa completed results tetap ada dan future rounds akan di-redraw tanpa
pemain tersebut.

## 25. Redrawing

Ketika roster berubah, jangan diam-diam mengganti drawing.

Tampilkan:

> Future rounds will change. Rounds 1--3 are already completed and will
> not be changed. Rounds 4--7 will be regenerated.

Actions:

**Redraw Future Rounds** / **Cancel**

## 26. Empty States

Empty states sederhana dan useful.

    No games yet.

    Create your first game and MainPadel
    will handle the draw.

    [ New Game ]

Tidak perlu illustration besar.

## 27. Game Completion

Setelah final round:

    GAME COMPLETE

    Friday Padel

    Winner
    HELMY
    68 points

    Final Standings
    ...

    [ View All Results ]
    [ New Game ]

Celebration boleh sedikit lebih expressive, tetapi tetap restrained.

## 28. Components

Core reusable components:

-   Button
-   Input
-   NumberStepper
-   PlayerChip
-   PlayerRow
-   MatchCard
-   ScoreInput
-   RoundHeader
-   StatusBadge
-   StandingRow
-   BottomNavigation
-   ConfirmationModal
-   BottomSheet
-   Toast

Jangan membuat abstraction untuk komponen yang hanya digunakan sekali
kecuali meningkatkan maintainability.

## 29. Buttons

Minimum touch target: **44px**.

Primary action ideal: **48--52px** height.

Button text harus action-oriented seperti:

-   Generate Draw
-   Enter Result
-   Save Result
-   Continue to Round 4
-   Add Player

Hindari `Submit`, `OK`, atau `Confirm` jika action spesifik dapat
digunakan.

## 30. Cards

Tidak semua section harus menjadi card.

Gunakan card hanya untuk object yang memang memiliki boundary seperti
match, recent game, atau optional standings summary.

Hindari nested cards. Gunakan whitespace dan divider untuk hierarchy.

## 31. Icons

Gunakan satu icon library konsisten. Recommended: **Lucide**.

Icons digunakan untuk membantu scanning, bukan dekorasi.

## 32. Motion

Animation minimal.

Allowed:

-   button press feedback
-   score transition
-   bottom sheet
-   tab transition
-   toast
-   subtle standings reorder

Duration: sekitar **150--250ms**.

Tidak perlu animated gradients, floating objects, parallax, atau
excessive page transitions.

## 33. Responsive Behaviour

Mobile adalah primary target.

Tablet dapat meningkatkan content width.

Desktop tetap menggunakan centered application, bukan sidebar +
dashboard + widgets.

## 34. Accessibility

Minimum requirements:

-   touch targets \>= 44px
-   adequate text contrast
-   visible focus state
-   input memiliki labels
-   state tidak hanya dibedakan berdasarkan warna
-   buttons menggunakan semantic button elements
-   score input mendukung keyboard
-   navigation dapat digunakan tanpa pointer

## 35. Loading State

Drawing generation kemungkinan cepat.

Sediakan state:

> Creating a fair draw...

Jika proses sangat cepat, loading state tidak perlu dipaksakan tampil.

## 36. Error State

Error harus menjelaskan apa yang salah dan apa yang bisa dilakukan user.

Bad:

> Invalid input.

Good:

> Add at least 4 active players to generate a draw.

Bad:

> 422 Unprocessable Entity.

Good:

> Scores must total 21 points.

## 37. Anti AI-Slop Rules

MainPadel harus menghindari:

-   gradient hero background
-   giant marketing headline inside application
-   excessive pill-shaped components
-   glassmorphism
-   glowing cards
-   excessive shadows
-   random decorative blobs
-   purple/blue AI palette
-   excessive rounded rectangles
-   fake dashboard charts
-   unnecessary emoji
-   icon inside every text label
-   verbose helper text
-   cards around every section
-   overly animated interactions

Jika sebuah UI element tidak membantu user **draw, play, score, or
understand standings**, pertimbangkan untuk menghapusnya.

## 38. Key UX Principle

Pada setiap screen harus jelas:

> **What should I do next?**

Create Game: Add players → Generate Draw

Current Round: Play match → Enter Result

After result: Continue to Next Round

Roster changed: Redraw Future Rounds

Tournament finished: View Final Standings

MainPadel tidak boleh membuat organizer berpikir tentang bagaimana
sistem bekerja.

## 39. MVP Design Priority

### P0

-   Create Game
-   Player input
-   Drawing
-   Current Round
-   Score Entry
-   Standings

### P1

-   All Rounds
-   Late Join
-   Player Leave
-   Redrawing confirmation

### P2

-   Recent games
-   Game completion polish
-   animations
-   sharing

Implementation harus menyelesaikan P0 sebelum visual polish P1/P2.

## 40. Reference Experience

MainPadel should feel closer to **a focused sports scoring utility**
than **a tournament administration dashboard**.

The application should be usable by someone who opens it for the first
time while already standing beside a padel court.

Final design test:

> **Can the organizer look at the phone for 3 seconds and immediately
> know who plays next?**
