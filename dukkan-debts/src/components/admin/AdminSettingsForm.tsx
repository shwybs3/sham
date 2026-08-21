"use client";

import { useState } from "react";
import {
  Store,
  Megaphone,
  KeyRound,
  ShieldQuestion,
  Send,
  Lock,
  Save,
  Eye,
  EyeOff,
} from "lucide-react";
import { Card } from "@/components/ui/Card";
import { Input, Label } from "@/components/ui/Input";
import { Button } from "@/components/ui/Button";
import { Switch } from "@/components/ui/Switch";
import { apiFetch } from "@/lib/api-client";
import { useToast } from "@/components/ui/Toast";
import { SETTINGS_KEYS, isTrue } from "@/lib/settings-keys";

function SecretInput({ value, onChange, placeholder }: { value: string; onChange: (v: string) => void; placeholder?: string }) {
  const [visible, setVisible] = useState(false);
  return (
    <div className="relative">
      <Input
        type={visible ? "text" : "password"}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        dir="ltr"
        className="pe-11"
      />
      <button
        type="button"
        onClick={() => setVisible((v) => !v)}
        className="absolute left-3.5 top-1/2 -translate-y-1/2 text-muted hover:text-foreground"
      >
        {visible ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
      </button>
    </div>
  );
}

export function AdminSettingsForm({ initialSettings }: { initialSettings: Record<string, string> }) {
  const { push } = useToast();
  const [values, setValues] = useState(initialSettings);
  const [savingGeneral, setSavingGeneral] = useState(false);
  const [savingBanner, setSavingBanner] = useState(false);
  const [savingKeys, setSavingKeys] = useState(false);
  const [savingPassword, setSavingPassword] = useState(false);

  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");

  function set(key: string, v: string) {
    setValues((prev) => ({ ...prev, [key]: v }));
  }

  async function saveSettings(subset: string[], setLoading: (v: boolean) => void, successMsg: string) {
    setLoading(true);
    try {
      const payload: Record<string, string> = {};
      for (const key of subset) payload[key] = values[key] ?? "";
      await apiFetch("/api/settings", {
        method: "PATCH",
        body: JSON.stringify({ settings: payload }),
      });
      push(successMsg, "success");
    } catch (err) {
      push(err instanceof Error ? err.message : "حدث خطأ", "error");
    } finally {
      setLoading(false);
    }
  }

  async function handlePasswordChange(e: React.FormEvent) {
    e.preventDefault();
    setSavingPassword(true);
    try {
      await apiFetch("/api/settings", {
        method: "PATCH",
        body: JSON.stringify({ passwordChange: { currentPassword, newPassword } }),
      });
      push("تم تغيير كلمة المرور", "success");
      setCurrentPassword("");
      setNewPassword("");
    } catch (err) {
      push(err instanceof Error ? err.message : "حدث خطأ", "error");
    } finally {
      setSavingPassword(false);
    }
  }

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <Card className="p-5">
        <div className="mb-4 flex items-center gap-2 font-bold">
          <Store className="size-4.5 text-primary" /> عام
        </div>
        <div className="space-y-4">
          <div>
            <Label htmlFor="shopName">اسم الدكان</Label>
            <Input
              id="shopName"
              value={values[SETTINGS_KEYS.shopName] ?? ""}
              onChange={(e) => set(SETTINGS_KEYS.shopName, e.target.value)}
            />
          </div>
          <div className="flex items-center justify-between rounded-xl bg-surface-muted px-4 py-3">
            <div>
              <p className="text-sm font-semibold">إظهار الصفحة العامة</p>
              <p className="text-xs text-muted">السماح للزوار برؤية أسماء المدينين وديونهم بدون تسجيل دخول</p>
            </div>
            <Switch
              checked={isTrue(values[SETTINGS_KEYS.publicPageEnabled])}
              onChange={(v) => set(SETTINGS_KEYS.publicPageEnabled, String(v))}
            />
          </div>
          <Button
            size="sm"
            loading={savingGeneral}
            onClick={() => saveSettings([SETTINGS_KEYS.shopName, SETTINGS_KEYS.publicPageEnabled], setSavingGeneral, "تم حفظ الإعدادات العامة")}
          >
            <Save className="size-4" /> حفظ
          </Button>
        </div>
      </Card>

      <Card className="p-5">
        <div className="mb-4 flex items-center gap-2 font-bold">
          <Megaphone className="size-4.5 text-primary" /> الإعلان المتحرك
        </div>
        <div className="space-y-4">
          <div className="flex items-center justify-between rounded-xl bg-surface-muted px-4 py-3">
            <p className="text-sm font-semibold">تفعيل الشريط المتحرك</p>
            <Switch
              checked={isTrue(values[SETTINGS_KEYS.bannerEnabled])}
              onChange={(v) => set(SETTINGS_KEYS.bannerEnabled, String(v))}
            />
          </div>
          <div>
            <Label htmlFor="bannerText">نص الإعلان</Label>
            <Input
              id="bannerText"
              value={values[SETTINGS_KEYS.bannerText] ?? ""}
              onChange={(e) => set(SETTINGS_KEYS.bannerText, e.target.value)}
              placeholder="مثال: عروض هذا الأسبوع على الألبان 🧀"
            />
          </div>
          <Button
            size="sm"
            loading={savingBanner}
            onClick={() => saveSettings([SETTINGS_KEYS.bannerEnabled, SETTINGS_KEYS.bannerText], setSavingBanner, "تم حفظ الإعلان")}
          >
            <Save className="size-4" /> حفظ
          </Button>
        </div>
      </Card>

      <Card className="p-5">
        <div className="mb-1 flex items-center gap-2 font-bold">
          <KeyRound className="size-4.5 text-primary" /> مفاتيح المنصات الخارجية
        </div>
        <p className="mb-4 text-xs text-muted">
          تُستخدم لتفعيل تسجيل الدخول بجوجل في قسم الخبز، وحماية النماذج، والتنبيهات المستقبلية عبر تيليجرام.
        </p>
        <div className="space-y-4">
          <div>
            <Label>Google OAuth Client ID</Label>
            <SecretInput value={values[SETTINGS_KEYS.googleClientId] ?? ""} onChange={(v) => set(SETTINGS_KEYS.googleClientId, v)} />
          </div>
          <div>
            <Label>Google OAuth Client Secret</Label>
            <SecretInput value={values[SETTINGS_KEYS.googleClientSecret] ?? ""} onChange={(v) => set(SETTINGS_KEYS.googleClientSecret, v)} />
          </div>
          <div className="flex items-center gap-2 pt-2 text-xs text-muted">
            <ShieldQuestion className="size-4" />
            Cloudflare Turnstile
          </div>
          <div>
            <Label>Turnstile Site Key</Label>
            <SecretInput value={values[SETTINGS_KEYS.turnstileSiteKey] ?? ""} onChange={(v) => set(SETTINGS_KEYS.turnstileSiteKey, v)} />
          </div>
          <div>
            <Label>Turnstile Secret Key</Label>
            <SecretInput value={values[SETTINGS_KEYS.turnstileSecretKey] ?? ""} onChange={(v) => set(SETTINGS_KEYS.turnstileSecretKey, v)} />
          </div>
          <div className="flex items-center gap-2 pt-2 text-xs text-muted">
            <Send className="size-4" />
            بوت تيليجرام
          </div>
          <div>
            <Label>Bot Token</Label>
            <SecretInput value={values[SETTINGS_KEYS.telegramBotToken] ?? ""} onChange={(v) => set(SETTINGS_KEYS.telegramBotToken, v)} />
          </div>
          <div>
            <Label>Chat / Channel ID</Label>
            <SecretInput value={values[SETTINGS_KEYS.telegramChatId] ?? ""} onChange={(v) => set(SETTINGS_KEYS.telegramChatId, v)} />
          </div>

          <Button
            size="sm"
            loading={savingKeys}
            onClick={() =>
              saveSettings(
                [
                  SETTINGS_KEYS.googleClientId,
                  SETTINGS_KEYS.googleClientSecret,
                  SETTINGS_KEYS.turnstileSiteKey,
                  SETTINGS_KEYS.turnstileSecretKey,
                  SETTINGS_KEYS.telegramBotToken,
                  SETTINGS_KEYS.telegramChatId,
                ],
                setSavingKeys,
                "تم حفظ المفاتيح"
              )
            }
          >
            <Save className="size-4" /> حفظ المفاتيح
          </Button>
        </div>
      </Card>

      <Card className="p-5">
        <div className="mb-4 flex items-center gap-2 font-bold">
          <Lock className="size-4.5 text-primary" /> تغيير كلمة المرور
        </div>
        <form onSubmit={handlePasswordChange} className="space-y-4">
          <div>
            <Label htmlFor="currentPassword">كلمة المرور الحالية</Label>
            <Input id="currentPassword" type="password" value={currentPassword} onChange={(e) => setCurrentPassword(e.target.value)} required />
          </div>
          <div>
            <Label htmlFor="newPassword">كلمة المرور الجديدة</Label>
            <Input id="newPassword" type="password" value={newPassword} onChange={(e) => setNewPassword(e.target.value)} minLength={6} required />
          </div>
          <Button type="submit" size="sm" loading={savingPassword}>
            <Save className="size-4" /> تحديث كلمة المرور
          </Button>
        </form>
      </Card>
    </div>
  );
}
