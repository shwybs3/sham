import Link from "next/link";
import { Wheat, ShieldCheck, Store } from "lucide-react";

export function SiteHeader({ shopName }: { shopName: string }) {
  return (
    <header className="sticky top-0 z-40 border-b border-border/70 bg-surface/85 backdrop-blur-lg">
      <div className="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
        <Link href="/" className="flex items-center gap-2.5 group">
          <span className="flex size-10 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm shadow-primary/30 transition-transform group-hover:scale-105">
            <Store className="size-5" />
          </span>
          <span className="text-lg font-extrabold tracking-tight">{shopName}</span>
        </Link>

        <nav className="flex items-center gap-1.5 sm:gap-2">
          <Link
            href="/bread"
            className="flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold text-muted transition-colors hover:bg-surface-muted hover:text-foreground"
          >
            <Wheat className="size-4" />
            <span className="hidden sm:inline">تسجيل الخبز</span>
          </Link>
          <Link
            href="/admin/login"
            title="لوحة الإدارة"
            className="flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold text-muted transition-colors hover:bg-surface-muted hover:text-foreground"
          >
            <ShieldCheck className="size-4" />
            <span className="hidden sm:inline">لوحة الإدارة</span>
          </Link>
        </nav>
      </div>
    </header>
  );
}
