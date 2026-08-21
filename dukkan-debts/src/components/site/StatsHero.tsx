import { Wallet2, Users2, TrendingUp } from "lucide-react";
import { formatMoney, CURRENCY_LABEL } from "@/lib/format";

export function StatsHero({
  totalOutstanding,
  debtorsCount,
  customersCount,
}: {
  totalOutstanding: number;
  debtorsCount: number;
  customersCount: number;
}) {
  const stats = [
    {
      icon: Wallet2,
      label: "إجمالي الديون المستحقة",
      value: `${formatMoney(totalOutstanding)} ${CURRENCY_LABEL}`,
      tone: "text-danger bg-danger-soft",
    },
    {
      icon: TrendingUp,
      label: "عدد المدينين حالياً",
      value: debtorsCount.toLocaleString("ar"),
      tone: "text-accent bg-accent-soft",
    },
    {
      icon: Users2,
      label: "إجمالي الزبائن المسجّلين",
      value: customersCount.toLocaleString("ar"),
      tone: "text-primary bg-primary-soft",
    },
  ];

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
      {stats.map((s) => (
        <div
          key={s.label}
          className="animate-fade-up flex items-center gap-4 rounded-2xl border border-border bg-surface p-5"
        >
          <span className={`flex size-12 shrink-0 items-center justify-center rounded-2xl ${s.tone}`}>
            <s.icon className="size-6" />
          </span>
          <div className="min-w-0">
            <p className="text-xs font-medium text-muted">{s.label}</p>
            <p className="truncate text-xl font-extrabold" dir="ltr">
              {s.value}
            </p>
          </div>
        </div>
      ))}
    </div>
  );
}
