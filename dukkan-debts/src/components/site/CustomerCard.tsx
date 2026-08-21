"use client";

import Link from "next/link";
import { motion } from "framer-motion";
import { Receipt, ChevronLeft } from "lucide-react";
import { formatMoney, formatDate, CURRENCY_LABEL } from "@/lib/format";
import type { PublicCustomer } from "@/lib/data";

function initials(name: string) {
  return name.trim().slice(0, 2);
}

export function CustomerCard({ customer, index }: { customer: PublicCustomer; index: number }) {
  const isPositive = customer.balance > 0.009;
  const isSettled = !isPositive;

  return (
    <motion.div
      initial={{ opacity: 0, y: 14 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.35, delay: Math.min(index, 10) * 0.03 }}
    >
      <Link
        href={`/customer/${customer.id}`}
        className="group flex flex-col gap-4 rounded-2xl border border-border bg-surface p-5 shadow-sm shadow-black/[0.02] transition-all hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-lg hover:shadow-primary/[0.06]"
      >
        <div className="flex items-center gap-3">
          <span className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary-soft text-base font-extrabold text-primary">
            {initials(customer.name)}
          </span>
          <div className="min-w-0 flex-1">
            <h3 className="truncate text-[15px] font-bold text-foreground">{customer.name}</h3>
            <p className="flex items-center gap-1 text-xs text-muted">
              <Receipt className="size-3.5" />
              {customer.transactionCount} عملية · آخر تحديث {formatDate(customer.lastActivity)}
            </p>
          </div>
          <ChevronLeft className="size-4 text-muted transition-transform group-hover:-translate-x-1" />
        </div>

        <div
          className={`flex items-center justify-between rounded-xl px-3.5 py-2.5 text-sm font-bold ${
            isSettled ? "bg-success-soft text-success" : "bg-danger-soft text-danger"
          }`}
        >
          <span>{isSettled ? "لا يوجد دين" : "إجمالي الدين"}</span>
          <span dir="ltr">
            {formatMoney(Math.abs(customer.balance))} {CURRENCY_LABEL}
          </span>
        </div>
      </Link>
    </motion.div>
  );
}
