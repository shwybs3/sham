"use client";

import { useEffect, useState } from "react";
import { Wheat, Users2, CalendarDays, Pencil, Trash2, Save, X } from "lucide-react";
import { Card } from "@/components/ui/Card";
import { Input } from "@/components/ui/Input";
import { Button } from "@/components/ui/Button";
import { apiFetch } from "@/lib/api-client";
import { useToast } from "@/components/ui/Toast";
import { useConfirm } from "@/components/ui/ConfirmDialog";
import { formatDateTime } from "@/lib/format";

type BreadCustomer = {
  id: string;
  name: string;
  email: string;
  avatarUrl: string | null;
  totalQuantity: number;
  entryCount: number;
};

type BreadEntry = {
  id: string;
  quantity: number;
  note: string | null;
  entryDate: string;
  customer: { id: string; name: string; email: string; avatarUrl: string | null };
};

function todayIso() {
  return new Date().toISOString().slice(0, 10);
}

export function AdminBreadManager() {
  const [customers, setCustomers] = useState<BreadCustomer[]>([]);
  const [entries, setEntries] = useState<BreadEntry[]>([]);
  const [date, setDate] = useState(todayIso());
  const [loading, setLoading] = useState(true);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [editQty, setEditQty] = useState("");
  const { push } = useToast();
  const confirm = useConfirm();

  async function loadCustomers() {
    try {
      const data = await apiFetch<{ customers: BreadCustomer[] }>("/api/admin/bread/customers");
      setCustomers(data.customers);
    } catch {
      /* noop */
    }
  }

  async function loadEntries(d: string) {
    try {
      const data = await apiFetch<{ entries: BreadEntry[] }>(`/api/admin/bread/entries?date=${d}`);
      setEntries(data.entries);
    } catch {
      /* noop */
    }
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- initial + refetch on date change
    setLoading(true);
    Promise.all([loadCustomers(), loadEntries(date)]).finally(() => setLoading(false));
  }, [date]);

  async function handleDelete(id: string) {
    const ok = await confirm({ message: "سيتم حذف هذا التسجيل نهائياً.", confirmLabel: "حذف", danger: true });
    if (!ok) return;
    try {
      await apiFetch(`/api/admin/bread/entries/${id}`, { method: "DELETE" });
      setEntries((prev) => prev.filter((e) => e.id !== id));
      push("تم الحذف", "success");
      loadCustomers();
    } catch (err) {
      push(err instanceof Error ? err.message : "حدث خطأ", "error");
    }
  }

  async function saveEdit(id: string) {
    const qty = Number(editQty);
    if (!qty || qty <= 0) {
      push("كمية غير صحيحة", "error");
      return;
    }
    try {
      await apiFetch(`/api/admin/bread/entries/${id}`, { method: "PATCH", body: JSON.stringify({ quantity: qty }) });
      setEntries((prev) => prev.map((e) => (e.id === id ? { ...e, quantity: qty } : e)));
      setEditingId(null);
      push("تم التحديث", "success");
      loadCustomers();
    } catch (err) {
      push(err instanceof Error ? err.message : "حدث خطأ", "error");
    }
  }

  return (
    <div className="space-y-6">
      <Card className="p-5">
        <div className="mb-4 flex items-center gap-2 font-bold">
          <Users2 className="size-4.5 text-primary" /> زبائن قسم الخبز ({customers.length})
        </div>
        {customers.length === 0 ? (
          <p className="py-6 text-center text-sm text-muted">لا يوجد زبائن مسجّلين بعد عبر جوجل</p>
        ) : (
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            {customers.map((c) => (
              <div key={c.id} className="flex items-center gap-3 rounded-xl border border-border p-3">
                {c.avatarUrl ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={c.avatarUrl} alt={c.name} className="size-10 rounded-full" />
                ) : (
                  <span className="flex size-10 items-center justify-center rounded-full bg-primary-soft font-bold text-primary">
                    {c.name.slice(0, 2)}
                  </span>
                )}
                <div className="min-w-0 flex-1">
                  <p className="truncate text-sm font-semibold">{c.name}</p>
                  <p className="truncate text-xs text-muted">{c.email}</p>
                </div>
                <div className="text-end">
                  <p className="font-extrabold text-primary">{c.totalQuantity}</p>
                  <p className="text-[11px] text-muted">ربطة</p>
                </div>
              </div>
            ))}
          </div>
        )}
      </Card>

      <Card className="p-5">
        <div className="mb-4 flex items-center justify-between gap-3">
          <div className="flex items-center gap-2 font-bold">
            <Wheat className="size-4.5 text-primary" /> تسجيلات اليوم
          </div>
          <div className="flex items-center gap-2">
            <CalendarDays className="size-4 text-muted" />
            <Input type="date" value={date} onChange={(e) => setDate(e.target.value)} className="h-9 w-auto" />
          </div>
        </div>

        {loading ? (
          <p className="py-6 text-center text-sm text-muted">جارِ التحميل…</p>
        ) : entries.length === 0 ? (
          <p className="py-6 text-center text-sm text-muted">لا توجد تسجيلات في هذا اليوم</p>
        ) : (
          <ul className="space-y-2">
            {entries.map((e) => (
              <li key={e.id} className="flex items-center gap-3 rounded-xl border border-border p-3">
                <div className="min-w-0 flex-1">
                  <p className="truncate text-sm font-semibold">{e.customer.name}</p>
                  <p className="text-xs text-muted">{formatDateTime(e.entryDate)}</p>
                </div>
                {editingId === e.id ? (
                  <div className="flex items-center gap-1.5">
                    <Input
                      type="number"
                      value={editQty}
                      onChange={(ev) => setEditQty(ev.target.value)}
                      className="h-9 w-20"
                    />
                    <Button size="sm" onClick={() => saveEdit(e.id)}>
                      <Save className="size-3.5" />
                    </Button>
                    <Button size="sm" variant="secondary" onClick={() => setEditingId(null)}>
                      <X className="size-3.5" />
                    </Button>
                  </div>
                ) : (
                  <>
                    <span className="font-extrabold text-primary">{e.quantity}</span>
                    <button
                      onClick={() => {
                        setEditingId(e.id);
                        setEditQty(String(e.quantity));
                      }}
                      className="flex size-8 items-center justify-center rounded-lg text-muted hover:bg-surface-muted hover:text-primary"
                    >
                      <Pencil className="size-3.5" />
                    </button>
                    <button
                      onClick={() => handleDelete(e.id)}
                      className="flex size-8 items-center justify-center rounded-lg text-muted hover:bg-danger-soft hover:text-danger"
                    >
                      <Trash2 className="size-3.5" />
                    </button>
                  </>
                )}
              </li>
            ))}
          </ul>
        )}
      </Card>
    </div>
  );
}
