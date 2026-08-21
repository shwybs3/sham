import Link from "next/link";
import { Wallet2, Users2, ShoppingCart, HandCoins, ChevronLeft } from "lucide-react";
import { prisma } from "@/lib/prisma";
import { getPublicCustomers, getShopStats } from "@/lib/data";
import { formatDateTime, formatMoney, CURRENCY_LABEL } from "@/lib/format";
import { Card } from "@/components/ui/Card";
import { QuickAddWidget } from "@/components/admin/QuickAddWidget";

export const dynamic = "force-dynamic";

export default async function AdminDashboardPage() {
  const [customers, stats, recentTransactions] = await Promise.all([
    getPublicCustomers(),
    getShopStats(),
    prisma.transaction.findMany({
      orderBy: { createdAt: "desc" },
      take: 10,
      include: { customer: { select: { name: true, id: true } } },
    }),
  ]);

  const liteCustomers = customers.map((c) => ({ id: c.id, name: c.name, balance: c.balance }));
  const topDebtors = customers.filter((c) => c.balance > 0.009).slice(0, 6);

  return (
    <div className="mx-auto max-w-6xl space-y-6">
      <div>
        <h1 className="text-2xl font-extrabold">لوحة التحكم</h1>
        <p className="text-sm text-muted">نظرة سريعة على وضع الديون اليوم</p>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <Card className="flex items-center gap-4 p-5">
          <span className="flex size-12 items-center justify-center rounded-2xl bg-danger-soft text-danger">
            <Wallet2 className="size-6" />
          </span>
          <div>
            <p className="text-xs text-muted">إجمالي الديون</p>
            <p className="text-xl font-extrabold" dir="ltr">
              {formatMoney(stats.totalOutstanding)} {CURRENCY_LABEL}
            </p>
          </div>
        </Card>
        <Card className="flex items-center gap-4 p-5">
          <span className="flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-accent">
            <Users2 className="size-6" />
          </span>
          <div>
            <p className="text-xs text-muted">عدد المدينين</p>
            <p className="text-xl font-extrabold">{stats.debtorsCount.toLocaleString("ar")}</p>
          </div>
        </Card>
        <Card className="flex items-center gap-4 p-5">
          <span className="flex size-12 items-center justify-center rounded-2xl bg-primary-soft text-primary">
            <Users2 className="size-6" />
          </span>
          <div>
            <p className="text-xs text-muted">كل الزبائن</p>
            <p className="text-xl font-extrabold">{stats.customersCount.toLocaleString("ar")}</p>
          </div>
        </Card>
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <Card className="p-5 lg:col-span-1">
          <QuickAddWidget customers={liteCustomers} />
        </Card>

        <div className="space-y-6 lg:col-span-2">
          <Card className="p-5">
            <div className="mb-3 flex items-center justify-between">
              <h2 className="font-bold">أكبر المدينين</h2>
              <Link href="/admin/customers" className="flex items-center gap-1 text-xs font-semibold text-primary">
                عرض الكل <ChevronLeft className="size-3.5" />
              </Link>
            </div>
            {topDebtors.length === 0 ? (
              <p className="py-6 text-center text-sm text-muted">لا يوجد ديون مسجّلة بعد 🎉</p>
            ) : (
              <div className="space-y-2">
                {topDebtors.map((c) => (
                  <Link
                    key={c.id}
                    href={`/admin/customers/${c.id}`}
                    className="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm transition-colors hover:bg-surface-muted"
                  >
                    <span className="font-semibold">{c.name}</span>
                    <span className="font-bold text-danger" dir="ltr">
                      {formatMoney(c.balance)} {CURRENCY_LABEL}
                    </span>
                  </Link>
                ))}
              </div>
            )}
          </Card>

          <Card className="p-5">
            <h2 className="mb-3 font-bold">آخر العمليات</h2>
            {recentTransactions.length === 0 ? (
              <p className="py-6 text-center text-sm text-muted">لا توجد عمليات بعد</p>
            ) : (
              <ul className="space-y-2">
                {recentTransactions.map((t) => {
                  const isDebt = t.type === "DEBT";
                  return (
                    <li key={t.id} className="flex items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-surface-muted">
                      <span
                        className={`flex size-9 shrink-0 items-center justify-center rounded-xl ${
                          isDebt ? "bg-danger-soft text-danger" : "bg-success-soft text-success"
                        }`}
                      >
                        {isDebt ? <ShoppingCart className="size-4" /> : <HandCoins className="size-4" />}
                      </span>
                      <div className="min-w-0 flex-1">
                        <Link href={`/admin/customers/${t.customer.id}`} className="truncate text-sm font-semibold hover:underline">
                          {t.customer.name}
                        </Link>
                        <p className="text-xs text-muted">{formatDateTime(t.createdAt)}</p>
                      </div>
                      <span className={`text-sm font-bold ${isDebt ? "text-danger" : "text-success"}`} dir="ltr">
                        {isDebt ? "+" : "-"}
                        {formatMoney(t.amount)} {CURRENCY_LABEL}
                      </span>
                    </li>
                  );
                })}
              </ul>
            )}
          </Card>
        </div>
      </div>
    </div>
  );
}
