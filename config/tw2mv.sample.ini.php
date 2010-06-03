; <?php /* ----------------------------------------------------------
; tw2mv setting file
; 
; Copyright 2010, nojimage (http://php-tips.com/)
;
; Licensed under The MIT License
; Redistributions of files must retain the above copyright notice.
;
; [;] セミコロンから始まる行はコメント行です。
; 設定パラメータを有効にする場合は、行頭のセミコロンを削除してください。
; -------------------------------------------------------------------
; ===================================================================
; CORE Section
; ===================================================================
;; mixiボイス から Twitterの転送を行うか（on/off デフォルト: on）
;core.mv2tw = on

;; Twitter から mixiボイスの転送を行うか（on/off デフォルト: on）
;core.tw2mv = on

;; 実際の投稿は行わず、IDの取得のみを行うか（yes/no デフォルト: no）
;core.fetch_only = no

;; 実行時にパスワードを暗号化して設定ファイルを書き換えるか（yes/no デフォルト: yes）
;core.password_crypt = yes;

; ===================================================================
; mixi Section
; ===================================================================
;; [必須] mixiのログインEmail
mixi.email    = ""

;; [必須] mixiのログインパスワード
mixi.password = ""

; -------------------------------------------------------------------
; mixi voice Section
; -------------------------------------------------------------------
;; mixiボイスへの投稿間隔 (単位: 秒)
;mixi_voice.post_interval = 2

;; mixiボイスに投稿するときメッセージ末尾に追加される文字列
;mixi_voice.message_suffix = " [tw2mv]"

;; mixiボイスの自分の発言を処理するか（yes/no デフォルト: yes）
;mixi_voice.myvoice_parse = yes

;; mixiボイスの自分への返信を処理するか（yes/no デフォルト: yes）
;mixi_voice.reply_parse   = yes

;; メッセージ取得時、mixiボイスの発言から他のユーザへの返信を除外するか（yes/no デフォルト: yes）
;mixi_voice.exclude_reply = yes

;; mixiボイスのメッセージ取得時、除外するキーワード
;mixi_voice.filter.denys[]  = "除外キーワードA"
;mixi_voice.filter.denys[]  = "除外キーワードB"

;; mixiボイスのメッセージ取得時、許可するキーワード
;mixi_voice.filter.allows[] = "許可キーワードA"
;mixi_voice.filter.allows[] = "許可キーワードB"

; ===================================================================
; Twitter Section
; ===================================================================
;; [必須] twitterのログインユーザ名
twitter.username = ""

;; [必須] twitterのログインパスワード
twitter.password = ""

;; twitterの最大処理発言数
;twitter.max_status = 20

;; twitterに投稿するとき末尾に付加される文字列
;twitter.message_suffix = " #mv2tw"

;; メッセージ取得時、twitterの発言から他のユーザへの返信を除外するか（yes/no デフォルト: yes）
;twitter.exclude_reply = yes

;; 返信メッセージの判定を簡易的に行うか（yes/no デフォルト: yes）
;; yes の場合、先頭に「@」がついたものは全て返信として扱われます。
;; no の場合、in_reply_to_* が存在するもののみ返信として扱われます。(v2.1.1以前の動作
;twitter.loose_reply_match = yes

;; メッセージ取得時、tw2mvを利用してmixiから転送されたメッセージを除外するか（yes/no デフォルト: yes）
;twitter.exclude_tw2mv = yes

;; twitterのメッセージ取得時、除外するキーワード
;twitter.filter.denys[]  = "除外キーワードA"
;twitter.filter.denys[]  = "除外キーワードB"

;; twitterのメッセージ取得時、許可するキーワード
;twitter.filter.allows[] = "許可キーワードA"
;twitter.filter.allows[] = "許可キーワードB"
