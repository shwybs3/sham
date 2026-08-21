"use client";

import { useMemo, useRef, useState } from "react";
import { Search, X, Users } from "lucide-react";
import { AnimatePresence, motion } from "framer-motion";
import Link from "next/link";
import { CustomerCard } from "@/components/site/CustomerCard";
import { formatMoney, CURRENCY_LABEL } from "@/lib/format";
import type { PublicCustomer } from "@/lib/data";

export function CustomerBrowser({ customers }: { customers: PublicCustomer[] }) {
  const [query, setQuery] = useState("");
  const [focused, setFocused] = useState(false);
  const inputRef = useRef<HTMLInputElement>(null);

  const suggestions = useMemo(() => {
    if (query.trim().length < 2) return [];
    const q = query.trim();
    return customers.filter((c) => c.name.includes(q)).slice(0, 6);
  }, [query, customers]);

  const filtered = useMemo(() => {
    const q = query.trim();
    if (!q) return customers;
    return customers.filter((c) => c.name.includes(q));
  }, [query, customers]);

  return (
    <div>
      <div className="relative mb-8">
        <div className="relative">
          <Search className="pointer-events-none absolute right-4 top-1/2 size-5 -translate-y-1/2 text-muted" />
          <input
            ref={inputRef}
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            onFocus={() => setFocused(true)}
            onBlur={() => setTimeout(() => setFocused(false), 150)}
            placeholder="ابحث باسم الزبون… (يكفي حرفان)"
            className="h-14 w-full rounded-2xl border border-border bg-surface pe-12 ps-12 text-[15px] font-medium text-foreground placeholder:text-muted shadow-sm outline-none transition-all focus:border-primary focus:ring-4 focus:ring-primary/10"
          />
          {query && (
            <button
              onClick={() => {
                setQuery("");
                inputRef.current?.focus();
              }}
              className="absolute left-4 top-1/2 -translate-y-1/2 rounded-full p-1 text-muted transition-colors hover:bg-surface-muted hover:text-foreground"
            >
              <X className="size-4" />
            </button>
          )}
        </div>

        <AnimatePresence>
          {focused && suggestions.length > 0 && (
            <motion.div
              initial={{ opacity: 0, y: -6 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -6 }}
              transition={{ duration: 0.15 }}
              className="absolute z-20 mt-2 w-full overflow-hidden rounded-2xl border border-border bg-surface shadow-xl"
            >
              {suggestions.map((s) => (
                <Link
                  key={s.id}
                  href={`/customer/${s.id}`}
                  className="flex items-center justify-between px-4 py-3 text-sm transition-colors hover:bg-surface-muted"
                >
                  <span className="font-semibold">{s.name}</span>
                  <span className={s.balance > 0.009 ? "text-danger" : "text-success"} dir="ltr">
                    {formatMoney(Math.abs(s.balance))} {CURRENCY_LABEL}
                  </span>
                </Link>
              ))}
            </motion.div>
          )}
        </AnimatePresence>
      </div>

      {filtered.length === 0 ? (
        <div className="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-border py-16 text-center">
          <Users className="size-10 text-muted" />
          <p className="font-semibold text-muted">لا يوجد زبائن مطابقين لبحثك</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {filtered.map((c, i) => (
            <CustomerCard key={c.id} customer={c} index={i} />
          ))}
        </div>
      )}
    </div>
  );
}
