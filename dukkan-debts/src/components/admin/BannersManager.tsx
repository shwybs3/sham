"use client";

import { useState } from "react";
import { ImagePlus, Trash2, Upload, Loader2 } from "lucide-react";
import { Input } from "@/components/ui/Input";
import { Button } from "@/components/ui/Button";
import { Switch } from "@/components/ui/Switch";
import { apiFetch } from "@/lib/api-client";
import { useToast } from "@/components/ui/Toast";
import { useConfirm } from "@/components/ui/ConfirmDialog";

type Banner = {
  id: string;
  imageUrl: string | null;
  linkUrl: string | null;
  text: string | null;
  active: boolean;
};

export function BannersManager({ initialBanners }: { initialBanners: Banner[] }) {
  const [banners, setBanners] = useState(initialBanners);
  const [imageUrl, setImageUrl] = useState("");
  const [linkUrl, setLinkUrl] = useState("");
  const [text, setText] = useState("");
  const [uploading, setUploading] = useState(false);
  const [saving, setSaving] = useState(false);
  const { push } = useToast();
  const confirm = useConfirm();

  async function handleUpload(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    e.target.value = "";
    if (!file) return;
    setUploading(true);
    try {
      const form = new FormData();
      form.append("file", file);
      const res = await fetch("/api/upload", { method: "POST", body: form });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error);
      setImageUrl(data.url);
      push("تم رفع الصورة", "success");
    } catch (err) {
      push(err instanceof Error ? err.message : "فشل رفع الصورة", "error");
    } finally {
      setUploading(false);
    }
  }

  async function handleAdd(e: React.FormEvent) {
    e.preventDefault();
    if (!imageUrl && !text) {
      push("أضف صورة أو نصاً على الأقل", "error");
      return;
    }
    setSaving(true);
    try {
      const data = await apiFetch<{ banner: Banner }>("/api/banners", {
        method: "POST",
        body: JSON.stringify({ imageUrl, linkUrl, text, active: true }),
      });
      setBanners((prev) => [...prev, data.banner]);
      setImageUrl("");
      setLinkUrl("");
      setText("");
      push("تمت إضافة البانر", "success");
    } catch (err) {
      push(err instanceof Error ? err.message : "حدث خطأ", "error");
    } finally {
      setSaving(false);
    }
  }

  async function toggleActive(banner: Banner) {
    try {
      await apiFetch(`/api/banners/${banner.id}`, {
        method: "PATCH",
        body: JSON.stringify({ active: !banner.active }),
      });
      setBanners((prev) => prev.map((b) => (b.id === banner.id ? { ...b, active: !b.active } : b)));
    } catch (err) {
      push(err instanceof Error ? err.message : "حدث خطأ", "error");
    }
  }

  async function handleDelete(id: string) {
    const ok = await confirm({ message: "سيتم حذف هذا البانر نهائياً.", confirmLabel: "حذف", danger: true });
    if (!ok) return;
    try {
      await apiFetch(`/api/banners/${id}`, { method: "DELETE" });
      setBanners((prev) => prev.filter((b) => b.id !== id));
      push("تم حذف البانر", "success");
    } catch (err) {
      push(err instanceof Error ? err.message : "حدث خطأ", "error");
    }
  }

  return (
    <div className="space-y-4">
      {banners.length > 0 && (
        <ul className="space-y-2">
          {banners.map((b) => (
            <li key={b.id} className="flex items-center gap-3 rounded-xl border border-border p-3">
              {b.imageUrl ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={b.imageUrl} alt="بانر" className="h-12 w-20 shrink-0 rounded-lg object-cover" />
              ) : (
                <span className="flex h-12 w-20 shrink-0 items-center justify-center rounded-lg bg-surface-muted text-muted">
                  <ImagePlus className="size-5" />
                </span>
              )}
              <div className="min-w-0 flex-1">
                {b.text && <p className="truncate text-sm font-semibold">{b.text}</p>}
                {b.linkUrl && <p className="truncate text-xs text-muted">{b.linkUrl}</p>}
              </div>
              <Switch checked={b.active} onChange={() => toggleActive(b)} />
              <button
                onClick={() => handleDelete(b.id)}
                className="flex size-8 items-center justify-center rounded-lg text-muted hover:bg-danger-soft hover:text-danger"
              >
                <Trash2 className="size-4" />
              </button>
            </li>
          ))}
        </ul>
      )}

      <form onSubmit={handleAdd} className="space-y-3 rounded-xl border border-dashed border-border p-4">
        <div className="flex items-center gap-2">
          <Input value={imageUrl} onChange={(e) => setImageUrl(e.target.value)} placeholder="رابط صورة البانر (اختياري)" dir="ltr" />
          <label className="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-xl border border-border text-muted hover:bg-surface-muted">
            {uploading ? <Loader2 className="size-4 animate-spin" /> : <Upload className="size-4" />}
            <input type="file" accept="image/*" className="hidden" onChange={handleUpload} disabled={uploading} />
          </label>
        </div>
        <Input value={linkUrl} onChange={(e) => setLinkUrl(e.target.value)} placeholder="رابط عند الضغط (اختياري)" dir="ltr" />
        <Input value={text} onChange={(e) => setText(e.target.value)} placeholder="نص البانر (اختياري)" />
        <Button type="submit" size="sm" loading={saving}>
          <ImagePlus className="size-4" /> إضافة بانر
        </Button>
      </form>
    </div>
  );
}
