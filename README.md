# ForumAI – AI‑Moderated Forum for Moodle 4.5

**ForumAI** is a drop‑in replacement for Moodle’s native **mod_forum** that adds manual and AI‑powered pre‑moderation. It relies on the new **AI Subsystem** shipped with Moodle 4.5, so no extra libraries are required.

---

## ✨ Key Features

| Capability            | Details                                                                                                           |
| --------------------- | ----------------------------------------------------------------------------------------------------------------- |
| **AI moderation**     | Posts are sent to any provider configured in *Site Admin → AI tools*. The model flags SPAM and offensive content. |
| **Manual queue**      | Teachers (or any role with `mod/forumai:moderate`) review messages before they become visible.                    |
| **Auto‑publish**      | Safe posts can be released automatically if the forum owner enables *AI autopublish*.                             |
| **Drop‑in install**   | Fork of `mod/forum`—all standard forum functionality remains.                                                     |
| **Privacy compliant** | Uses core AI API → logs prompts/responses in the standard privacy subsystem.                                      |

---

## 🚀 Installation

1. Clone the plugin into `mod/forumai` under your Moodle root:
   ```bash
   git clone https://github.com/<you>/forumai.git mod/forumai
   ```
2. Visit *Site administration → Notifications* to run the upgrade.
3. Go to *Site administration → AI tools* and add/configure at least one provider (e.g. OpenAI).

> **Requires** Moodle 4.5 + PHP 8.1.

---

## ⚡ Quick Start

1. Create a new **ForumAI** activity in a course.
2. In the *Moderation* section choose:
   - **Enable moderation** ✔️
   - **Mode** → *AI* or *Manual*
   - *(optional)* **AI autopublish** ✔️ (safe posts go live instantly)
3. Post a message as a student—check the moderation queue.

---

## 🔧 Configuration Options

| Setting               | Purpose                                                 |
| --------------------- | ------------------------------------------------------- |
| **Enable moderation** | Activates the queue for this forum instance.            |
| **Moderation mode**   | `Manual` → human review, `AI` → send to provider first. |
| **AI autopublish**    | Skip human review when AI marks the post safe.          |

Global capabilities are defined in `db/access.php`:

- `mod/forumai:postwithoutmoderation`
- `mod/forumai:moderate`

---

## 🤖 How AI Moderation Works

1. On `post_created` the observer calls `\core_ai\manager::process_action()` with a JSON‑only prompt.
2. The provider returns `{ "spam": true|false, "offensive": true|false }`.
3. Depending on result, the post status becomes:
   - `autoapproved` → visible immediately
   - `pending` → awaits teacher review
   - `rejected` → never shown (teacher can override)

Full code lives in `classes/local/ai_moderator.php`.

---

## 🔄 Syncing with Upstream `mod/forum`

This repository tracks Moodle core as an *upstream* remote. To merge the latest fixes:

```bash
git fetch upstream
git merge upstream/MOODLE_405_STABLE    # or later stable branch
```

The working tree only contains ForumAI files thanks to sparse‑checkout.

---

## 🙌 Contributing

Pull Requests are welcome! Please:

1. Follow Moodle coding style (see `phpcs.xml` in root).
2. Add PHPUnit and Behat tests for any new behaviour.
3. Target the `main` branch.

---

## 📄 License

GPL v3 – same as Moodle core.

---

## 🖋️ Credits

Built by **Walter López** and contributors, inspired by Moodle HQ’s AI Subsystem. Feel free to send feedback or open issues.
