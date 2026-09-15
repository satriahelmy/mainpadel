# MainPadel --- Product Requirements Document

**Version:** 1.0\
**Status:** Draft MVP\
**Product:** MainPadel\
**Type:** Responsive Web Application

## 1. Product Overview

MainPadel adalah web application sederhana untuk mengelola sesi
permainan padel dengan format rotasi individual/Americano.

Organizer memasukkan daftar pemain dan jumlah court yang tersedia.
MainPadel kemudian membuat drawing pertandingan secara otomatis dengan
mempertimbangkan pemerataan jumlah bermain, waktu istirahat, variasi
partner, variasi lawan, dan bentrok pemain.

Setelah setiap pertandingan selesai, organizer memasukkan skor.
MainPadel menghitung klasemen individual secara otomatis berdasarkan
hasil seluruh pertandingan dalam sesi tersebut.

MainPadel ditujukan terutama untuk permainan padel kasual bersama teman,
bukan sebagai platform pengelolaan turnamen profesional.

## 2. Problem

Saat bermain padel dengan jumlah pemain lebih banyak daripada kapasitas
court, diperlukan pengaturan manual mengenai:

-   siapa bermain;
-   siapa menjadi partner;
-   siapa menjadi lawan;
-   siapa beristirahat;
-   siapa sudah terlalu sering bermain;
-   partner yang sudah pernah berpasangan;
-   pencatatan skor;
-   klasemen individual.

Contoh: 6 pemain datang dan hanya tersedia 1 court. Setiap pertandingan
membutuhkan 4 pemain sehingga setiap round harus menentukan 4 pemain
yang bermain dan 2 pemain yang beristirahat.

Pengaturan manual semakin sulit ketika jumlah pemain bertambah atau
pemain datang/pulang di tengah sesi.

## 3. Product Goal

MainPadel harus memungkinkan organizer menjalankan sesi dengan flow
sesingkat mungkin:

    Create Game
        ↓
    Add Players
        ↓
    Set Courts
        ↓
    Generate Draw
        ↓
    Play
        ↓
    Enter Score
        ↓
    Next Round
        ↓
    Live Standings

Target pengalaman pengguna:

> Dalam kurang dari satu menit, organizer sudah dapat membuat sesi dan
> mendapatkan drawing pertandingan pertama.

## 4. MVP Scope

### 4.1 Create Tournament / Session

User dapat membuat sesi permainan baru.

Field:

-   Session Name
-   Date
-   Number of Courts
-   Target Points
-   Round Mode

Default:

-   Target Points = 21
-   Round Mode = Auto

Istilah internal database boleh menggunakan `Tournament`, tetapi pada UI
sebaiknya menggunakan istilah yang lebih kasual seperti **Game** atau
**Session**.

## 5. Player Management

Organizer dapat memasukkan pemain secara manual.

Minimum pemain: **4 players**.

Jumlah pemain tidak harus kelipatan empat. MainPadel harus mendukung
misalnya:

-   5 players / 1 court
-   6 players / 1 court
-   7 players / 1 court
-   8 players / 1--2 courts
-   10 players / 1--2 courts
-   14 players / 1--3 courts

Maximum active court:

`floor(active_players / 4)`

Jika user memiliki 6 pemain tetapi memasukkan 2 court, sistem memberi
informasi bahwa 6 pemain hanya dapat menggunakan maksimal 1 court secara
bersamaan.

## 6. Match Format

Satu match selalu terdiri dari **2 players vs 2 players**.

Default permainan menggunakan format **21 total points**.

Contoh skor valid:

-   11--10
-   12--9
-   15--6
-   18--3

Constraint:

`team_a_score + team_b_score = target_points`

Target point harus configurable.

## 7. Round

Tournament terdiri dari beberapa round.

Contoh 6 pemain / 1 court:

    ROUND 1

    Court 1
    A + B
      VS
    C + D

    Rest
    E, F

Round berikutnya menggunakan kombinasi berbeda.

## 8. Drawing Engine

Drawing Engine merupakan fitur inti MainPadel.

Algoritma **tidak boleh hanya melakukan random shuffle**. Drawing harus
mempertimbangkan hard constraints dan fairness constraints.

### 8.1 Hard Constraints

#### H1 --- Player Collision

Satu pemain tidak boleh berada pada dua pertandingan dalam round yang
sama.

#### H2 --- Match Size

Setiap match harus memiliki tepat empat pemain.

#### H3 --- Team Size

Setiap team harus memiliki tepat dua pemain.

#### H4 --- Active Player

Hanya pemain dengan status `active` yang boleh dimasukkan ke drawing.

#### H5 --- Court Capacity

Jumlah match dalam satu round:

`min(number_of_courts, floor(active_players / 4))`

## 9. Drawing Fairness

Selain hard constraints, algoritma harus mengoptimalkan fairness.

### Priority 1 --- Balanced Matches Played

Selisih jumlah pertandingan antar pemain harus diminimalkan.

### Priority 2 --- Balanced Rest

Pemain yang sudah lebih sering beristirahat harus mendapat prioritas
bermain.

### Priority 3 --- Partner Diversity

Algoritma sebisa mungkin menghindari pasangan yang sama berulang kali.

### Priority 4 --- Opponent Diversity

Pemain sebisa mungkin menghadapi lawan yang berbeda.

### Priority 5 --- Consecutive Rest

Sebisa mungkin hindari pemain mendapatkan rest pada beberapa round
berturut-turut.

## 10. Drawing Scoring Function

Implementasi disarankan menggunakan candidate generation + penalty
scoring.

Secara konseptual:

    penalty =
        match_count_imbalance
      + rest_imbalance
      + repeated_partner_penalty
      + repeated_opponent_penalty
      + consecutive_rest_penalty

Candidate drawing dengan penalty terkecil dipilih.

Hard constraints harus diperiksa terlebih dahulu dan tidak boleh
dikompensasi oleh penalty score.

Bobot penalty harus dibuat configurable di code agar mudah dituning
selama development.

## 11. Round Generation

Untuk MVP tersedia dua mode.

### Auto

MainPadel menentukan rotasi secara otomatis. Tujuan Auto bukan menjamin
seluruh kombinasi matematis dimainkan, tetapi menghasilkan jumlah round
yang cukup untuk memberikan rotasi pemain yang fair.

### Manual

Organizer menentukan jumlah round secara eksplisit. MainPadel kemudian
membuat drawing sesuai jumlah tersebut.

Untuk implementasi awal, Manual Rounds dapat diselesaikan terlebih
dahulu sementara heuristic Auto dituning setelah drawing engine stabil.

## 12. Score Entry

Setelah match selesai, organizer memasukkan hasil.

Validation:

-   `score_a >= 0`
-   `score_b >= 0`
-   `score_a + score_b = target_points`

Setelah disimpan:

`Match status = completed`

Hasil langsung masuk ke standings.

## 13. Individual Standings

Klasemen dihitung berdasarkan **pemain**, bukan pasangan.

Jika:

`Helmy + Andi 12 — 9 Budi + Fajar`

maka Helmy dan Andi masing-masing mendapatkan:

-   12 Points For
-   9 Points Against
-   1 Win

Budi dan Fajar masing-masing mendapatkan:

-   9 Points For
-   12 Points Against
-   1 Loss

Leaderboard minimal menampilkan:

`Rank | Player | P | W | L | PF | PA | +/-`

Dengan:

-   P = Played
-   W = Win
-   L = Loss
-   PF = Points For
-   PA = Points Against
-   +/- = PF - PA

Ranking utama MVP:

1.  Points For
2.  Point Difference
3.  Wins

Ranking rules harus dibuat configurable agar mudah diubah kemudian.

## 14. Live Tournament

Setelah permainan dimulai, halaman utama session menampilkan current
round, seluruh court pada round tersebut, pemain yang resting, dan next
action.

Organizer dapat berpindah antara:

-   Current Round / Play
-   All Rounds
-   Standings
-   Players

## 15. Late Join

Pemain dapat ditambahkan ketika session sudah berjalan.

MainPadel harus:

-   tidak mengubah completed rounds;
-   menambahkan pemain sebagai active player;
-   menyimpan `joined_at_round`;
-   regenerate hanya future rounds;
-   mempertimbangkan jumlah match pemain baru dalam fairness
    calculation.

Sistem tidak harus menjamin pemain late join dapat mengejar jumlah
pertandingan pemain lama.

## 16. Player Leave

Pemain dapat berhenti bermain di tengah session.

MainPadel mengubah status menjadi `withdrawn`.

Match yang sudah completed tetap valid. Future rounds di-regenerate
tanpa pemain tersebut. Historical score dan standings pemain tetap
tersimpan.

## 17. Regeneration Rules

Drawing dapat berubah selama round tersebut belum dimainkan.

**Completed matches are immutable.**

Jika roster berubah:

-   completed rounds tetap;
-   current/future unplayed rounds di-regenerate.

User harus mendapat konfirmasi sebelum drawing future rounds diganti.

## 18. Data Model

Core entities:

-   Tournament
-   Player
-   TournamentPlayer
-   Round
-   Match
-   MatchPlayer

Relationship:

    Tournament
    │
    ├── TournamentPlayer
    │       └── Player
    │
    └── Round
          │
          └── Match
                │
                └── MatchPlayer
                        └── Player

Satu tournament/session memiliki satu individual leaderboard.

## 19. Tournament

Suggested fields:

-   id
-   name
-   played_at
-   number_of_courts
-   target_points
-   round_mode
-   number_of_rounds
-   status
-   created_at
-   updated_at

Status:

-   draft
-   ongoing
-   completed
-   cancelled

## 20. Players

Fields:

-   id
-   name
-   created_at
-   updated_at

Untuk MVP, player tidak membutuhkan account/login.

## 21. Tournament Players

Fields:

-   id
-   tournament_id
-   player_id
-   status
-   joined_at_round
-   left_at_round
-   created_at
-   updated_at

Status:

-   active
-   withdrawn

## 22. Rounds

Fields:

-   id
-   tournament_id
-   round_number
-   status
-   created_at
-   updated_at

Status:

-   scheduled
-   ongoing
-   completed

## 23. Matches

Fields:

-   id
-   round_id
-   court_number
-   team_a_score
-   team_b_score
-   status
-   created_at
-   updated_at

Status:

-   scheduled
-   completed

## 24. Match Players

Fields:

-   id
-   match_id
-   player_id
-   team

`team`:

-   A
-   B

Satu match harus memiliki tepat 2 pemain Team A dan 2 pemain Team B.

## 25. Standings Storage

Untuk MVP **tidak diperlukan tabel standings**.

Standings dihitung dari `matches + match_players` untuk seluruh
completed matches dalam satu tournament.

Keuntungan:

-   tidak ada duplicated state;
-   hasil selalu konsisten dengan match;
-   koreksi skor otomatis mengubah standings.

Jika kemudian performa menjadi masalah, standings dapat di-cache.

## 26. Edge Cases

MainPadel minimal harus menangani:

-   kurang dari 4 active players;
-   jumlah court lebih besar dari possible matches;
-   odd number of players;
-   player added mid-session;
-   player withdrawn mid-session;
-   score tidak berjumlah target points;
-   future drawing regenerated;
-   duplicate player names;
-   unfinished matches.

Jika active player turun di bawah 4:

> Tidak cukup pemain untuk membuat pertandingan berikutnya.

## 27. MVP Screens

MVP membutuhkan screen utama:

1.  Home
2.  Create Session
3.  Players
4.  Drawing / Current Round
5.  Input Score
6.  Standings
7.  All Rounds

Prioritas UI adalah **mobile-first**, karena aplikasi kemungkinan besar
digunakan sambil berada di court.

## 28. Technical Direction

Recommended stack:

-   Laravel
-   MySQL
-   Blade
-   Alpine.js
-   Tailwind CSS

Drawing logic harus dipisahkan dari controller.

Suggested structure:

    app/
    └── Services/
        └── Drawing/
            ├── DrawingService.php
            ├── CandidateGenerator.php
            ├── DrawingScorer.php
            └── DrawingValidator.php

Tujuannya agar algoritma drawing dapat diuji secara independen.

## 29. Testing Requirement

Drawing engine wajib memiliki automated tests.

Minimal test cases:

-   4 players / 1 court
-   5 players / 1 court
-   6 players / 1 court
-   8 players / 1 court
-   8 players / 2 courts
-   10 players / 2 courts
-   12 players / 2 courts
-   12 players / 3 courts
-   player joins mid-session
-   player leaves mid-session

Test harus memastikan:

-   no player collision;
-   exactly 4 players/match;
-   exactly 2 players/team;
-   valid number of courts;
-   balanced match counts;
-   balanced rests;
-   completed rounds unchanged.

Fairness metrics juga sebaiknya dapat dihitung dalam test sehingga
kualitas algoritma dapat dibandingkan ketika logic drawing diubah.

## 30. Out of Scope --- V1

Sengaja tidak masuk MVP:

-   Authentication
-   User account
-   Payment
-   Subscription
-   Venue management
-   Court booking
-   League management
-   Team tournament
-   Public player profiles
-   Player rating / ELO
-   Social features
-   Chat
-   Push notification
-   AI
-   Native mobile app

MainPadel V1 adalah **utility**, bukan SaaS.

## 31. Success Criteria

MVP dianggap berhasil jika organizer dapat:

> memasukkan pemain → menghasilkan drawing yang valid → menjalankan
> beberapa round tanpa bentrok → memasukkan skor → melihat klasemen
> individual yang benar

termasuk ketika pemain datang atau pulang di tengah sesi.

Core product principle:

> **MainPadel should make organizing the game easier than using
> WhatsApp + Notes.**
