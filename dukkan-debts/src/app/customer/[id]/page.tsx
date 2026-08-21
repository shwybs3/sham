import { notFound } from "next/navigation";
import Link from "next/link";
import { ArrowRight, ShoppingCart, HandCoins, Phone, StickyNote } from "lucide-react";
import { prisma } from "@/lib/prisma";
import { computeBalance } from "@/lib/balance";
import { formatDateTime, formatMoney, CURRENCY_LABEL } from "@/lib/format";
import { getSettings, SETTINGS_KEYS } from "@/lib/settings";
import { SiteHeader } from "@/components/site/SiteHeader";
import { SiteFooter } from "@/components/site/SiteFooter";
import { ChatWidget } from "@/components/chat/ChatWidget";

export const dynamic = "force-dynamic";

export default async function CustomerPage({ params }: PageProps<"/customer/[id]">) {
  const { id } = await params;
  const [customer, settings] = await Promise.all([
    prisma.customer.findUnique({
      where: { id },
      include: { transactions: { orderBy: { createdAt: "desc" } } },
    }),
    getSettings(),
  ]);

  if (!customer) notFound();

  const shopName = settings[SETTINGS_KEYS.shopName];
  const balance = computeBalance(customer.transactions);
  const isSettled = balance <= 0.009;

  return (
    <div className="flex min-h-screen flex-col">
      <SiteHeader shopName={shopName} />

      <main className="mx-auto w-full max-w-3xl flex-1 px-4 py-8 sm:px-6">
        <Link href="/" className="mb-6 inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-foreground">
          <ArrowRight className="size-4" />
          العودة إلى كل الزبائن
        </Link>

        <div className="animate-fade-up rounded-2xl border border-border bg-surface p-6">
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

            <div
              className={`rounded-2xl px-5 py-3 text-center font-extrabold ${
                isSettled ? "bg-success-soft text-success" : "bg-danger-soft text-danger"
              }`}
            >
              <p className="text-xs font-medium opacity-80">{isSettled ? "لا يوجد دين" : "إجمالي الدين"}</p>
              <p className="text-xl" dir="ltr">
                {formatMoney(Math.abs(balance))} {CURRENCY_LABEL}
              </p>
            </div>
          </div>
        </div>

        <h2 className="mb-4 mt-8 text-lg font-bold">سجل المشتريات والدفعات</h2>

        {customer.transactions.length === 0 ? (
          <p className="rounded-2xl border border-dashed border-border py-10 text-center text-sm text-muted">
            لا توجد أي عمليات مسجلة بعد.
          </p>
        ) : (
          <ol className="space-y-3">
            {customer.transactions.map((t) => {
              const isDebt = t.type === "DEBT";
              return (
                <li
                  key={t.id}
                  className="flex items-center gap-3 rounded-xl border border-border bg-surface p-4"
                >
                  <span
                    className={`flex size-10 shrink-0 items-center justify-center rounded-xl ${
                      isDebt ? "bg-danger-soft text-danger" : "bg-success-soft text-success"
                    }`}
                  >
                    {isDebt ? <ShoppingCart className="size-5" /> : <HandCoins className="size-5" />}
                  </span>
                  <div className="min-w-0 flex-1">
                    <p className="font-semibold">{isDebt ? "دين جديد" : "دفعة"}</p>
                    {t.description && <p className="truncate text-sm text-muted">{t.description}</p>}
                    <p className="text-xs text-muted">{formatDateTime(t.createdAt)}</p>
                  </div>
                  <p className={`font-bold ${isDebt ? "text-danger" : "text-success"}`} dir="ltr">
                    {isDebt ? "+" : "-"}
                    {formatMoney(t.amount)} {CURRENCY_LABEL}
                  </p>
                </li>
              );
            })}
          </ol>
        )}
      </main>

      <SiteFooter shopName={shopName} />
      <ChatWidget />
    </div>
  );
}
