"use client";

import { useState } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import {
  LayoutDashboard,
  Users,
  Settings,
  Wheat,
  MessageSquare,
  LogOut,
  Menu,
  X,
  Store,
  ExternalLink,
} from "lucide-react";
import { cn } from "@/lib/cn";
import { apiFetch } from "@/lib/api-client";

const NAV = [
  { href: "/admin", label: "لوحة التحكم", icon: LayoutDashboard, exact: true },
  { href: "/admin/customers", label: "الزبائن والديون", icon: Users },
  { href: "/admin/bread", label: "قسم الخبز", icon: Wheat },
  { href: "/admin/chat", label: "الدردشة", icon: MessageSquare },
  { href: "/admin/settings", label: "الإعدادات", icon: Settings },
];

export function AdminShell({
  shopName,
  username,
  children,
}: {
  shopName: string;
  username: string;
  children: React.ReactNode;
}) {
  const pathname = usePathname();
  const router = useRouter();
  const [mobileOpen, setMobileOpen] = useState(false);

  async function handleLogout() {
    await apiFetch("/api/admin/logout", { method: "POST" });
    router.push("/admin/login");
    router.refresh();
  }

  const isActive = (href: string, exact?: boolean) =>
    exact ? pathname === href : pathname === href || pathname.startsWith(href + "/");

  const SidebarContent = (
    <div className="flex h-full flex-col">
      <div className="flex items-center gap-2.5 px-5 py-5">
        <span className="flex size-10 items-center justify-center rounded-xl bg-primary text-primary-foreground">
          <Store className="size-5" />
        </span>
        <div className="min-w-0">
          <p className="truncate text-sm font-extrabold">{shopName}</p>
          <p className="truncate text-xs text-muted">@{username}</p>
        </div>
      </div>

      <nav className="flex-1 space-y-1 px-3">
        {NAV.map((item) => (
          <Link
            key={item.href}
            href={item.href}
            onClick={() => setMobileOpen(false)}
            className={cn(
              "flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-semibold transition-colors",
              isActive(item.href, item.exact)
                ? "bg-primary-soft text-primary"
                : "text-muted hover:bg-surface-muted hover:text-foreground"
            )}
          >
            <item.icon className="size-4.5" />
            {item.label}
          </Link>
        ))}
      </nav>

      <div className="space-y-1 border-t border-border p-3">
        <Link
          href="/"
          target="_blank"
          className="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-muted transition-colors hover:bg-surface-muted hover:text-foreground"
        >
          <ExternalLink className="size-4.5" />
          عرض الموقع العام
        </Link>
        <button
          onClick={handleLogout}
          className="flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-danger transition-colors hover:bg-danger-soft"
        >
          <LogOut className="size-4.5" />
          تسجيل الخروج
        </button>
      </div>
    </div>
  );

  return (
    <div className="flex min-h-screen">
      <aside className="hidden w-64 shrink-0 border-l border-border bg-surface lg:block">
        {SidebarContent}
      </aside>

      {mobileOpen && (
        <div className="fixed inset-0 z-50 lg:hidden">
          <div className="absolute inset-0 bg-black/40" onClick={() => setMobileOpen(false)} />
          <div className="absolute inset-y-0 right-0 w-72 bg-surface shadow-2xl">
            <button
              onClick={() => setMobileOpen(false)}
              className="absolute left-3 top-4 flex size-8 items-center justify-center rounded-lg text-muted hover:bg-surface-muted"
            >
              <X className="size-5" />
            </button>
            {SidebarContent}
          </div>
        </div>
      )}

      <div className="flex min-h-screen flex-1 flex-col">
        <header className="sticky top-0 z-30 flex items-center gap-3 border-b border-border bg-surface/85 px-4 py-3 backdrop-blur-lg lg:hidden">
          <button
            onClick={() => setMobileOpen(true)}
            className="flex size-9 items-center justify-center rounded-lg text-foreground hover:bg-surface-muted"
          >
            <Menu className="size-5" />
          </button>
          <span className="font-extrabold">{shopName}</span>
        </header>

        <main className="flex-1 p-4 sm:p-6">{children}</main>
      </div>
    </div>
  );
}
