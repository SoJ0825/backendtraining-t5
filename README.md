# 以 Laravel 實作許願池的後端 API

## 題目：

團隊正在開發一套軟體系統，用來給組織的成員們寫下許願小卡，

請嘗試基於這個 Repository，以 Laravel Framework 開發出許願池系統需要的後端 API，並通過所有自動化測試。

---

### 許願小卡

使用者在註冊後，可以登入系統，來輸入自己想要提出的願望 ~~(會不會實現就看緣分了)~~，

由於個人隱私，只能看到自己的許願小卡，且可以隨時修改與刪除自己的小卡。

新增完小卡後，使用者可以標記小卡為「送出」，代表把這張小卡送進許願池裡，而已送出的許願卡不能被收回、修改、刪除，

如果想要收回某張誤送的小卡，需要聯繫許願池小精靈 aka. 管理員，請管理員協助收回。

### 許願池小精靈

管理員負責管理許願池的和平，可以查看所有許願小卡及使用者，除了使用者的密碼以外。

為了避免濫用，可以啟用與停用指定的使用者，
也可以協助修改使用者的資料與密碼，

並根據使用者的要求，將指定的已送出小卡回復為未送出。

---

## 規格

### 功能

1. 註冊登入
2. 許願小卡
    - C 新增一筆自己的小卡
    - R 讀取 (列表與指定小卡)
    - U 更新指定小卡
    - D 刪除指定小卡
    - 送出：標記指定的小卡為「送出」
3. 管理員
    - 使用者
        - 查看所有使用者 (列表與指定使用者)
        - 修改使用者
            - 各項資料
            - 密碼
            - 為啟用
            - 為停用
    - 許願小卡
        - 查看所有的小卡
        - 收回指定的已送出小卡
4. 資料庫 (一定要需紀錄的欄位)
    - `users` 表格
        - `email` 欄位
        - `password` 欄位
    - `wishes` 表格
        - `message` 欄位

### API 文件

啟動伺服器後，可進入 [`/api/documentation`](http://localhost:8000/api/documentation) 以 Swagger-UI 查看。

---

## 完成條件

1. 在這個 Repo 中，實現上述所有的功能。
1. 通過所有自動化測試 (使用者)。
1. 滿足 API 文件中的規格 (使用者 + 管理員)。
1. 建立 PR，並取得 2 個 Approve。

---

## 自動化測試

安裝完 PHP、Composer、專案的相依套件後，可以用下面的指令執行自動化測試。

### 版本限制

- PHP: ^8.0
- Laravel: ^9

(`^` 的意義請參考 [StackOverflow - What's the difference between tilde(~) and caret(^) in package.json?](https://stackoverflow.com/questions/22343224/whats-the-difference-between-tilde-and-caret-in-package-json/22345808))

### 指令

- 安裝套件
```bash
composer install
```

- 執行所有測試
```bash
composer test
# 或 php artisan test
```

- 執行單一檔案的測試
```bash
php artisan test tests/API/SomeTest.php
```
