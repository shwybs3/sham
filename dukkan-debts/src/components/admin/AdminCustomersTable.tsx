"use client";

import { useMemo, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Search, Trash2, ChevronLeft, UserPlus } from "lucide-react";
import { Input } from "@/components/ui/Input";
import { Button } from "@/components/ui/Button";
import { formatMoney, CURRENCY_LABEL } from "@/lib/format";
import { apiFetch } from "@/lib/api-client";
import { useToast } from "@/components/ui/Toast";
import { useConfirm } from "@/components/ui/ConfirmDialog";
import { AddCustomerDialog } from "@/components/admin/AddCustomerDialog";

type CustomerRow = {
  id: string;
  name: string;
  phone: string | null;
  balance: number;
  transactionCount: number;
};

export function AdminCustomersTable({ initialCustomers }: { initialCustomers: CustomerRow[] }) {
  const [query, setQuery] = useState("");
  const [customers, setCustomers] = useState(initialCustomers);
  const [addOpen, setAddOpen] = useState(false);
  const router = useRouter();
  const { push } = useToast();
  const confirm = useConfirm();

  const filtered = useMemo(() => {
    const q = query.trim();
    if (!q) return customers;
    return customers.filter((c) => c.name.includes(q));
  }, [query, customers]);

  async function handleDelete(id: string, name: string) {
    const ok = await confirm({
      title: "حذف الزبون",
      message: `سيتم حذف "${name}" وكل سجل ديونه ودفعاته نهائياً. لا يمكن التراجع عن هذا الإجراء.`,
      confirmLabel: "حذف نهائياً",
      danger: true,
    });
    if (!ok) return;

    try {
      await apiFetch(`/api/customers/${id}`, { method: "DELETE" });
      setCustomers((prev) => prev.filter((c) => c.id !== id));
      push("تم حذف الزبون", "success");
    } catch (err) {
      push(err instanceof Error ? err.message : "تعذر الحذف", "error");
    }
  }

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div className="relative flex-1">
          <Search className="pointer-events-none absolute right-3.5 top-1/2 size-4.5 -translate-y-1/2 text-muted" />
          <Input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="ابحث عن زبون…" className="pe-11" />
        </div>
        <Button onClick={() => setAddOpen(true)}>
          <UserPlus className="size-4" />
          زبون جديد
        </Button>
      </div>

      <div className="overflow-hidden rounded-2xl border border-border">
        <div className="max-h-[60vh] overflow-y-auto">
          <table className="w-full text-sm">
            <thead className="sticky top-0 bg-surface-muted text-xs text-muted">
              <tr>
                <th className="px-4 py-3 text-start font-semibold">الاسم</th>
                <th className="px-4 py-3 text-start font-semibold">عدد العمليات</th>
                <th className="px-4 py-3 text-start font-semibold">الرصيد</th>
                <th className="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((c) => (
                <tr key={c.id} className="border-t border-border transition-colors hover:bg-surface-muted/60">
                  <td className="px-4 py-3 font-semibold">
                    <Link href={`/admin/customers/${c.id}`} className="hover:underline">
                      {c.name}
                    </Link>
                    {c.phone && <span className="ms-2 text-xs text-muted">{c.phone}</span>}
                  </td>
                  <td className="px-4 py-3 text-muted">{c.transactionCount}</td>
                  <td className={`px-4 py-3 font-bold ${c.balance > 0.009 ? "text-danger" : "text-success"}`} dir="ltr">
                    {formatMoney(Math.abs(c.balance))} {CURRENCY_LABEL}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center justify-end gap-1">
                      <Link
                        href={`/admin/customers/${c.id}`}
                        className="flex size-8 items-center justify-center rounded-lg text-muted hover:bg-surface hover:text-primary"
                      >
                        <ChevronLeft className="size-4" />
                      </Link>
                      <button
                        onClick={() => handleDelete(c.id, c.name)}
                        className="flex size-8 items-center justify-center rounded-lg text-muted hover:bg-danger-soft hover:text-danger"
                      >
                        <Trash2 className="size-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
              {filtered.length === 0 && (
                <tr>
                  <td colSpan={4} className="px-4 py-10 text-center text-muted">
                    لا يوجد زبائن مطابقين
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      <AddCustomerDialog
        open={addOpen}
        onClose={() => setAddOpen(false)}
        onCreated={() => {
          setAddOpen(false);
          router.refresh();
        }}
      />
    </div>
  );
}
