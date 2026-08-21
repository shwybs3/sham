"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { Minus, Plus, Wheat, Trash2, LogOut, StickyNote } from "lucide-react";
import { Button } from "@/components/ui/Button";
import { Textarea } from "@/components/ui/Input";
import { apiFetch } from "@/lib/api-client";
import { useToast } from "@/components/ui/Toast";
import { useConfirm } from "@/components/ui/ConfirmDialog";
import { formatDateTime } from "@/lib/format";

type BreadEntry = {
  id: string;
  quantity: number;
  note: string | null;
  entryDate: string;
};

export function BreadDashboard({ name, avatarUrl }: { name: string; avatarUrl: string | null }) {
  const router = useRouter();
  const { push } = useToast();
  const confirm = useConfirm();

  const [quantity, setQuantity] = useState(1);
  const [note, setNote] = useState("");
  const [showNote, setShowNote] = useState(false);
  const [loading, setLoading] = useState(false);
  const [entries, setEntries] = useState<BreadEntry[]>([]);

  async function loadEntries() {
    try {
      const data = await apiFetch<{ entries: BreadEntry[] }>("/api/bread/entries");
      setEntries(data.entries);
    } catch {
      /* noop */
    }
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- initial fetch on mount
    loadEntries();
  }, []);

  async function handleSubmit() {
    setLoading(true);
    try {
      await apiFetch("/api/bread/entries", {
        method: "POST",
        body: JSON.stringify({ quantity, note: note || undefined }),
      });
      push("تم تسجيل الخبز بنجاح", "success");
      setQuantity(1);
      setNote("");
      setShowNote(false);
      loadEntries();
    } catch (err) {
      push(err instanceof Error ? err.message : "حدث خطأ", "error");
    } finally {
      setLoading(false);
    }
  }

  async function handleDelete(id: string) {
    const ok = await confirm({ message: "سيتم حذف هذا التسجيل.", confirmLabel: "حذف", danger: true });
    if (!ok) return;
    try {
      await apiFetch(`/api/bread/entries/${id}`, { method: "DELETE" });
      setEntries((prev) => prev.filter((e) => e.id !== id));
      push("تم الحذف", "success");
    } catch (err) {
      push(err instanceof Error ? err.message : "حدث خطأ", "error");
    }
  }

  async function handleLogout() {
    await apiFetch("/api/bread/auth/logout", { method: "POST" });
    router.refresh();
  }

  const todayTotal = entries
    .filter((e) => new Date(e.entryDate).toDateString() === new Date().toDateString())
    .reduce((sum, e) => sum + e.quantity, 0);

  return (
    <div className="mx-auto max-w-xl space-y-6 px-4 py-8">
      <div className="flex items-center gap-3">
        {avatarUrl ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={avatarUrl} alt={name} className="size-12 rounded-full" />
        ) : (
          <span className="flex size-12 items-center justify-center rounded-full bg-primary-soft text-lg font-bold text-primary">
            {name.slice(0, 2)}
          </span>
        )}
        <div className="flex-1">
          <p className="font-bold">{name}</p>
          <p className="text-xs text-muted">مرحباً بك في قسم الخبز</p>
        </div>
        <button onClick={handleLogout} className="flex size-9 items-center justify-center rounded-lg text-muted hover:bg-surface-muted hover:text-danger">
          <LogOut className="size-4" />
        </button>
      </div>

      <div className="animate-fade-up rounded-3xl border border-border bg-surface p-6 text-center">
        <span className="mx-auto mb-3 flex size-14 items-center justify-center rounded-2xl bg-accent-soft text-accent">
          <Wheat className="size-7" />
        </span>
        <p className="mb-4 text-sm text-muted">
          تسجيل خبز اليوم — لديك <span className="font-bold text-foreground">{todayTotal}</span> ربطة مسجّلة اليوم
        </p>

        <div className="mb-4 flex items-center justify-center gap-4">
          <button
            onClick={() => setQuantity((q) => Math.max(1, q - 1))}
            className="flex size-12 items-center justify-center rounded-2xl border border-border text-lg font-bold hover:bg-surface-muted"
          >
            <Minus className="size-5" />
          </button>
          <span className="w-16 text-4xl font-extrabold text-primary">{quantity}</span>
          <button
            onClick={() => setQuantity((q) => Math.min(50, q + 1))}
            className="flex size-12 items-center justify-center rounded-2xl border border-border text-lg font-bold hover:bg-surface-muted"
          >
            <Plus className="size-5" />
          </button>
        </div>

        {showNote ? (
          <Textarea
            value={note}
            onChange={(e) => setNote(e.target.value)}
            placeholder="ملاحظة (اختياري)"
            rows={2}
            className="mb-4"
          />
        ) : (
          <button
            onClick={() => setShowNote(true)}
            className="mb-4 flex items-center gap-1 text-xs font-semibold text-muted hover:text-foreground"
          >
            <StickyNote className="size-3.5" /> إضافة ملاحظة
          </button>
        )}

        <Button className="w-full" size="lg" onClick={handleSubmit} loading={loading}>
          تسجيل الآن
        </Button>
      </div>

      <div>
        <h2 className="mb-3 font-bold">سجلاتي الأخيرة</h2>
        {entries.length === 0 ? (
          <p className="rounded-2xl border border-dashed border-border py-8 text-center text-sm text-muted">لا توجد تسجيلات بعد</p>
        ) : (
          <ul className="space-y-2">
            {entries.slice(0, 20).map((e) => (
              <li key={e.id} className="flex items-center gap-3 rounded-xl border border-border p-3">
                <span className="flex size-9 items-center justify-center rounded-xl bg-accent-soft font-bold text-accent">
                  {e.quantity}
                </span>
                <div className="min-w-0 flex-1">
                  {e.note && <p className="truncate text-sm">{e.note}</p>}
                  <p className="text-xs text-muted">{formatDateTime(e.entryDate)}</p>
                </div>
                <button
                  onClick={() => handleDelete(e.id)}
                  className="flex size-8 items-center justify-center rounded-lg text-muted hover:bg-danger-soft hover:text-danger"
                >
                  <Trash2 className="size-3.5" />
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}
