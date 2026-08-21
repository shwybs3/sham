"use client";

import { useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { ShoppingCart, HandCoins, UserPlus, Zap } from "lucide-react";
import { Input, Label, Textarea } from "@/components/ui/Input";
import { Button } from "@/components/ui/Button";
import { cn } from "@/lib/cn";
import { apiFetch } from "@/lib/api-client";
import { useToast } from "@/components/ui/Toast";
import { formatMoney, CURRENCY_LABEL } from "@/lib/format";

export type LiteCustomer = { id: string; name: string; balance: number };

export function QuickAddWidget({ customers }: { customers: LiteCustomer[] }) {
  const router = useRouter();
  const { push } = useToast();

  const [type, setType] = useState<"DEBT" | "PAYMENT">("DEBT");
  const [name, setName] = useState("");
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [amount, setAmount] = useState("");
  const [description, setDescription] = useState("");
  const [loading, setLoading] = useState(false);
  const [showSuggestions, setShowSuggestions] = useState(false);

  const suggestions = useMemo(() => {
    if (name.trim().length < 2 || selectedId) return [];
    return customers.filter((c) => c.name.includes(name.trim())).slice(0, 6);
  }, [name, customers, selectedId]);

  const exactMatch = customers.some((c) => c.name === name.trim());

  function reset() {
    setName("");
    setSelectedId(null);
    setAmount("");
    setDescription("");
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    const trimmedName = name.trim();
    const numericAmount = Number(amount);

    if (!trimmedName) {
      push("يرجى إدخال اسم الزبون", "error");
      return;
    }
    if (!numericAmount || numericAmount <= 0) {
      push("يرجى إدخال مبلغ صحيح", "error");
      return;
    }

    setLoading(true);
    try {
      let customerId = selectedId;

      if (!customerId) {
        if (type === "DEBT") {
          const data = await apiFetch<{ customer: { id: string } }>("/api/customers", {
            method: "POST",
            body: JSON.stringify({
              name: trimmedName,
              initialDebt: numericAmount,
              initialDescription: description || undefined,
            }),
          });
          customerId = data.customer.id;
        } else {
          const created = await apiFetch<{ customer: { id: string } }>("/api/customers", {
            method: "POST",
            body: JSON.stringify({ name: trimmedName }),
          });
          customerId = created.customer.id;
          await apiFetch(`/api/customers/${customerId}/transactions`, {
            method: "POST",
            body: JSON.stringify({ type, amount: numericAmount, description: description || undefined }),
          });
        }
      } else {
        await apiFetch(`/api/customers/${customerId}/transactions`, {
          method: "POST",
          body: JSON.stringify({ type, amount: numericAmount, description: description || undefined }),
        });
      }

      push(type === "DEBT" ? "تم تسجيل الدين بنجاح" : "تم تسجيل الدفعة بنجاح", "success");
      reset();
      router.refresh();
    } catch (err) {
      push(err instanceof Error ? err.message : "حدث خطأ", "error");
    } finally {
      setLoading(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="flex items-center gap-2 text-sm font-bold text-primary">
        <Zap className="size-4" />
        إضافة سريعة
      </div>

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

      <div className="relative">
        <Label htmlFor="qa-name">اسم الزبون</Label>
        <Input
          id="qa-name"
          value={name}
          onChange={(e) => {
            setName(e.target.value);
            setSelectedId(null);
            setShowSuggestions(true);
          }}
          onFocus={() => setShowSuggestions(true)}
          onBlur={() => setTimeout(() => setShowSuggestions(false), 150)}
          placeholder="اكتب حرفين لبدء البحث…"
          autoComplete="off"
          required
        />
        {showSuggestions && suggestions.length > 0 && (
          <div className="absolute z-20 mt-1.5 w-full overflow-hidden rounded-xl border border-border bg-surface shadow-lg">
            {suggestions.map((c) => (
              <button
                type="button"
                key={c.id}
                onMouseDown={() => {
                  setName(c.name);
                  setSelectedId(c.id);
                  setShowSuggestions(false);
                }}
                className="flex w-full items-center justify-between px-3.5 py-2.5 text-sm hover:bg-surface-muted"
              >
                <span className="font-semibold">{c.name}</span>
                <span className={c.balance > 0.009 ? "text-danger" : "text-success"} dir="ltr">
                  {formatMoney(Math.abs(c.balance))} {CURRENCY_LABEL}
                </span>
              </button>
            ))}
          </div>
        )}
        {name.trim().length >= 2 && !exactMatch && !selectedId && (
          <p className="mt-1.5 flex items-center gap-1 text-xs font-medium text-accent">
            <UserPlus className="size-3.5" /> سيتم إنشاء زبون جديد بهذا الاسم
          </p>
        )}
      </div>

      <div>
        <Label htmlFor="qa-amount">المبلغ ({CURRENCY_LABEL})</Label>
        <Input
          id="qa-amount"
          type="number"
          inputMode="decimal"
          min="0"
          step="0.01"
          value={amount}
          onChange={(e) => setAmount(e.target.value)}
          placeholder="0"
          required
        />
      </div>

      <div>
        <Label htmlFor="qa-desc">ملاحظة (اختياري)</Label>
        <Textarea
          id="qa-desc"
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          rows={2}
          placeholder="مثال: خضار وفواكه"
        />
      </div>

      <Button type="submit" className="w-full" loading={loading}>
        حفظ العملية
      </Button>
    </form>
  );
}
