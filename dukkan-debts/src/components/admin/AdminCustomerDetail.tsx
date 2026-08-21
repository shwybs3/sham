"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import {
  ArrowRight,
  Phone,
  StickyNote,
  Pencil,
  Trash2,
  ShoppingCart,
  HandCoins,
  CheckCheck,
  Save,
  X,
} from "lucide-react";
import { Card } from "@/components/ui/Card";
import { Button } from "@/components/ui/Button";
import { Input, Label, Textarea } from "@/components/ui/Input";
import { cn } from "@/lib/cn";
import { apiFetch } from "@/lib/api-client";
import { useToast } from "@/components/ui/Toast";
import { useConfirm } from "@/components/ui/ConfirmDialog";
import { formatDateTime, formatMoney, CURRENCY_LABEL } from "@/lib/format";
import { Modal } from "@/components/ui/Modal";

type Transaction = {
  id: string;
  type: "DEBT" | "PAYMENT";
  amount: number;
  description: string | null;
  createdAt: string;
};

type CustomerDetail = {
  id: string;
  name: string;
  phone: string | null;
  notes: string | null;
  balance: number;
  transactions: Transaction[];
};

export function AdminCustomerDetail({ customer }: { customer: CustomerDetail }) {
  const router = useRouter();
  const { push } = useToast();
  const confirm = useConfirm();

  const [editOpen, setEditOpen] = useState(false);
  const [editName, setEditName] = useState(customer.name);
  const [editPhone, setEditPhone] = useState(customer.phone ?? "");
  const [editNotes, setEditNotes] = useState(customer.notes ?? "");
  const [savingEdit, setSavingEdit] = useState(false);

  const [type, setType] = useState<"DEBT" | "PAYMENT">("DEBT");
  const [amount, setAmount] = useState("");
  const [description, setDescription] = useState("");
  const [addLoading, setAddLoading] = useState(false);

  const [editingTxId, setEditingTxId] = useState<string | null>(null);
  const [editTxAmount, setEditTxAmount] = useState("");
  const [editTxDesc, setEditTxDesc] = useState("");

  const isSettled = customer.balance <= 0.009;

  async function handleAddTransaction(e: React.FormEvent) {
    e.preventDefault();
    const numericAmount = Number(amount);
    if (!numericAmount || numericAmount <= 0) {
      push("يرجى إدخال مبلغ صحيح", "error");
      return;
    }
    setAddLoading(true);
    try {
      await apiFetch(`/api/customers/${customer.id}/transactions`, {
        method: "POST",
        body: JSON.stringify({ type, amount: numericAmount, description: description || undefined }),
      });
      push(type === "DEBT" ? "تم تسجيل الدين" : "تم تسجيل الدفعة", "success");
      setAmount("");
      setDescription("");
      router.refresh();
    } catch (err) {
      push(err instanceof Error ? err.message : "حدث خطأ", "error");
    } finally {
      setAddLoading(false);
    }
  }

  async function handlePayAll() {
    const ok = await confirm({
      title: "تسديد كامل الدين",
      message: `سيتم تسجيل دفعة بقيمة ${formatMoney(customer.balance)} ${CURRENCY_LABEL} لتصفير رصيد ${customer.name}.`,
      confirmLabel: "تسديد الكل",
    });
    if (!ok) return;
    try {
      await apiFetch(`/api/customers/${customer.id}/transactions`, {
        method: "POST",
        body: JSON.stringify({ type: "PAYMENT", amount: customer.balance, description: "تسديد كامل الدين" }),
      });
      push("تم تسديد كامل الدين", "success");
      router.refresh();
    } catch (err) {
      push(err instanceof Error ? err.message : "حدث خطأ", "error");
    }
  }

  async function handleSaveEdit(e: React.FormEvent) {
    e.preventDefault();
    setSavingEdit(true);
    try {
      await apiFetch(`/api/customers/${customer.id}`, {
        method: "PATCH",
        body: JSON.stringify({ name: editName, phone: editPhone || null, notes: editNotes || null }),
      });
      push("تم تحديث بيانات الزبون", "success");
      setEditOpen(false);
      router.refresh();
    } catch (err) {
      push(err instanceof Error ? err.message : "حدث خطأ", "error");
    } finally {
      setSavingEdit(false);
    }
  }

  async function handleDeleteCustomer() {
    const ok = await confirm({
      title: "حذف الزبون",
      message: `سيتم حذف "${customer.name}" وكل سجله نهائياً. هذا الإجراء لا يمكن التراجع عنه.`,
      confirmLabel: "حذف نهائياً",
      danger: true,
    });
    if (!ok) return;
    try {
      await apiFetch(`/api/customers/${customer.id}`, { method: "DELETE" });
      push("تم حذف الزبون", "success");
      router.push("/admin/customers");
    } catch (err) {
      push(err instanceof Error ? err.message : "حدث خطأ", "error");
    }
  }

  async function handleDeleteTransaction(id: string) {
    const ok = await confirm({ message: "سيتم حذف هذه العملية نهائياً.", confirmLabel: "حذف", danger: true });
    if (!ok) return;
    try {
      await apiFetch(`/api/transactions/${id}`, { method: "DELETE" });
      push("تم حذف العملية", "success");
      router.refresh();
    } catch (err) {
      push(err instanceof Error ? err.message : "حدث خطأ", "error");
    }
  }

  function startEditTx(t: Transaction) {
    setEditingTxId(t.id);
    setEditTxAmount(String(t.amount));
    setEditTxDesc(t.description ?? "");
  }

  async function saveEditTx(id: string) {
    const numericAmount = Number(editTxAmount);
    if (!numericAmount || numericAmount <= 0) {
      push("مبلغ غير صحيح", "error");
      return;
    }
    try {
      await apiFetch(`/api/transactions/${id}`, {
        method: "PATCH",
        body: JSON.stringify({ amount: numericAmount, description: editTxDesc || null }),
      });
      push("تم تحديث العملية", "success");
      setEditingTxId(null);
      router.refresh();
    } catch (err) {
      push(err instanceof Error ? err.message : "حدث خطأ", "error");
    }
  }

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <Link href="/admin/customers" className="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-foreground">
        <ArrowRight className="size-4" />
        كل الزبائن
      </Link>

      <Card className="p-6">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 className="text-2xl font-extrabold">{customer.name}</h1>
            <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted">
              {customer.phone && (
                <span className="flex items-center gap-1">
                  <Phone className="size-3.5" /> {customer.phone}
                </span>
              )}
              {customer.notes && (
                <span className="flex items-center gap-1">
                  <StickyNote className="size-3.5" /> {customer.notes}
                </span>
              )}
            </div>
          </div>
          <div className={cn("rounded-2xl px-5 py-3 text-center font-extrabold", isSettled ? "bg-success-soft text-success" : "bg-danger-soft text-danger")}>
            <p className="text-xs font-medium opacity-80">{isSettled ? "لا يوجد دين" : "إجمالي الدين"}</p>
            <p className="text-xl" dir="ltr">
              {formatMoney(Math.abs(customer.balance))} {CURRENCY_LABEL}
            </p>
          </div>
        </div>

        <div className="mt-5 flex flex-wrap gap-2">
          <Button variant="outline" size="sm" onClick={() => setEditOpen(true)}>
            <Pencil className="size-4" /> تعديل البيانات
          </Button>
          {!isSettled && (
            <Button variant="secondary" size="sm" onClick={handlePayAll}>
              <CheckCheck className="size-4" /> تسديد كامل الدين
            </Button>
          )}
          <Button variant="danger" size="sm" onClick={handleDeleteCustomer} className="ms-auto">
            <Trash2 className="size-4" /> حذف الزبون
          </Button>
        </div>
      </Card>

      <Card className="p-5">
        <h2 className="mb-4 font-bold">إضافة عملية جديدة</h2>
        <form onSubmit={handleAddTransaction} className="space-y-3">
          <div className="grid grid-cols-2 gap-2">
            <button
              type="button"
              onClick={() => setType("DEBT")}
              className={cn(
                "flex items-center justify-center gap-2 rounded-xl border py-2.5 text-sm font-bold transition-colors",
                type === "DEBT" ? "border-danger bg-danger-soft text-danger" : "border-border text-muted"
              )}
            >
              <ShoppingCart className="size-4" /> دين جديد
            </button>
            <button
              type="button"
              onClick={() => setType("PAYMENT")}
              className={cn(
                "flex items-center justify-center gap-2 rounded-xl border py-2.5 text-sm font-bold transition-colors",
                type === "PAYMENT" ? "border-success bg-success-soft text-success" : "border-border text-muted"
              )}
            >
              <HandCoins className="size-4" /> دفعة
            </button>
          </div>
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <Input
              type="number"
              inputMode="decimal"
              min="0"
              step="0.01"
              value={amount}
              onChange={(e) => setAmount(e.target.value)}
              placeholder={`المبلغ (${CURRENCY_LABEL})`}
              required
            />
            <Input value={description} onChange={(e) => setDescription(e.target.value)} placeholder="ملاحظة (اختياري)" />
          </div>
          <Button type="submit" loading={addLoading}>
            حفظ العملية
          </Button>
        </form>
      </Card>

      <div>
        <h2 className="mb-3 font-bold">سجل العمليات ({customer.transactions.length})</h2>
        {customer.transactions.length === 0 ? (
          <p className="rounded-2xl border border-dashed border-border py-10 text-center text-sm text-muted">لا توجد عمليات بعد</p>
        ) : (
          <ul className="space-y-2">
            {customer.transactions.map((t) => {
              const isDebt = t.type === "DEBT";
              const isEditing = editingTxId === t.id;
              return (
                <li key={t.id} className="rounded-xl border border-border bg-surface p-4">
                  {isEditing ? (
                    <div className="space-y-2">
                      <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <Input
                          type="number"
                          value={editTxAmount}
                          onChange={(e) => setEditTxAmount(e.target.value)}
                          step="0.01"
                        />
                        <Input value={editTxDesc} onChange={(e) => setEditTxDesc(e.target.value)} placeholder="ملاحظة" />
                      </div>
                      <div className="flex gap-2">
                        <Button size="sm" onClick={() => saveEditTx(t.id)}>
                          <Save className="size-4" /> حفظ
                        </Button>
                        <Button size="sm" variant="secondary" onClick={() => setEditingTxId(null)}>
                          <X className="size-4" /> إلغاء
                        </Button>
                      </div>
                    </div>
                  ) : (
                    <div className="flex items-center gap-3">
                      <span
                        className={cn(
                          "flex size-10 shrink-0 items-center justify-center rounded-xl",
                          isDebt ? "bg-danger-soft text-danger" : "bg-success-soft text-success"
                        )}
                      >
                        {isDebt ? <ShoppingCart className="size-5" /> : <HandCoins className="size-5" />}
                      </span>
                      <div className="min-w-0 flex-1">
                        <p className="font-semibold">{isDebt ? "دين جديد" : "دفعة"}</p>
                        {t.description && <p className="truncate text-sm text-muted">{t.description}</p>}
                        <p className="text-xs text-muted">{formatDateTime(t.createdAt)}</p>
                      </div>
                      <p className={cn("font-bold", isDebt ? "text-danger" : "text-success")} dir="ltr">
                        {isDebt ? "+" : "-"}
                        {formatMoney(t.amount)} {CURRENCY_LABEL}
                      </p>
                      <div className="flex items-center gap-1">
                        <button
                          onClick={() => startEditTx(t)}
                          className="flex size-8 items-center justify-center rounded-lg text-muted hover:bg-surface-muted hover:text-primary"
                        >
                          <Pencil className="size-3.5" />
                        </button>
                        <button
                          onClick={() => handleDeleteTransaction(t.id)}
                          className="flex size-8 items-center justify-center rounded-lg text-muted hover:bg-danger-soft hover:text-danger"
                        >
                          <Trash2 className="size-3.5" />
                        </button>
                      </div>
                    </div>
                  )}
                </li>
              );
            })}
          </ul>
        )}
      </div>

      <Modal open={editOpen} onClose={() => setEditOpen(false)} title="تعديل بيانات الزبون">
        <form onSubmit={handleSaveEdit} className="space-y-4">
          <div>
            <Label htmlFor="e-name">الاسم</Label>
            <Input id="e-name" value={editName} onChange={(e) => setEditName(e.target.value)} required />
          </div>
          <div>
            <Label htmlFor="e-phone">رقم الهاتف</Label>
            <Input id="e-phone" value={editPhone} onChange={(e) => setEditPhone(e.target.value)} dir="ltr" />
          </div>
          <div>
            <Label htmlFor="e-notes">ملاحظات</Label>
            <Textarea id="e-notes" value={editNotes} onChange={(e) => setEditNotes(e.target.value)} rows={2} />
          </div>
          <Button type="submit" className="w-full" loading={savingEdit}>
            حفظ التعديلات
          </Button>
        </form>
      </Modal>
    </div>
  );
}
