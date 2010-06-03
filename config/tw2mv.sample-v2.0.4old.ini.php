; <?php /* ----------------------------------------------------------
; tw2mv setting file (older version 2.0.4)
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
[CORE]
;; mixiボイス から Twitterの転送を行うか（on/off デフォルト: on）
;mv2tw = on

;; Twitter から mixiボイスの転送を行うか（on/off デフォルト: on）
;tw2mv = on

;; 実際の投稿は行わず、IDの取得のみを行うか（yes/no デフォルト: no）
;fetch_only = no

;; 実行時にパスワードを暗号化して設定ファイルを書き換えるか（yes/no デフォルト: yes）
;password_crypt = yes;

; ===================================================================
[MIXI]
;; [必須] mixiのログインEmail
email    = ""

;; [必須] mixiのログインパスワード
password = ""

; -------------------------------------------------------------------
[MIXI_VOICE]
;; mixiボイスへの投稿間隔 (単位: 秒)
;post_interval = 2

;; mixiボイスに投稿するときメッセージ末尾に追加される文字列
;message_suffix = " [tw2mv]"

;; mixiボイスの自分の発言を処理するか（yes/no デフォルト: yes）
;myvoice_parse = yes

;; mixiボイスの自分への返信を処理するか（yes/no デフォルト: yes）
;reply_parse   = yes

;; メッセージ取得時、mixiボイスの発言から他のユーザへの返信を除外するか（yes/no デフォルト: yes）
;exclude_reply = yes

;; mixiボイスのメッセージ取得時、除外するキーワード
;filter.denys[]  = "除外キーワードA"
;filter.denys[]  = "除外キーワードB"

;; mixiボイスのメッセージ取得時、許可するキーワード
;filter.allows[] = "許可キーワードA"
;filter.allows[] = "許可キーワードB"

; ===================================================================
[TWITTER]
;; [必須] twitterのログインユーザ名
username = ""

;; [必須] twitterのログインパスワード
password = ""

;; twitterの最大処理発言数
;max_status = 20

;; twitterに投稿するとき末尾に付加される文字列
;message_suffix = " #mv2tw"

;; メッセージ取得時、twitterの発言から他のユーザへの返信を除外するか（yes/no デフォルト: yes）
;exclude_reply = yes

;; twitterのメッセージ取得時、除外するキーワード
;filter.denys[]  = "除外キーワードA"
;filter.denys[]  = "除外キーワードB"

;; twitterのメッセージ取得時、許可するキーワード
;filter.allows[] = "許可キーワードA"
;filter.allows[] = "許可キーワードB"
