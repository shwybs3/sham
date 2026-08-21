import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { AuthLayout } from "@/components/admin/AuthLayout";
import { AdminLoginForm } from "@/components/admin/AdminLoginForm";

export const dynamic = "force-dynamic";

export default async function AdminLoginPage({ searchParams }: PageProps<"/admin/login">) {
  const adminCount = await prisma.adminUser.count();
  if (adminCount === 0) redirect("/admin/setup");

  const sp = await searchParams;
  const nextRaw = Array.isArray(sp.next) ? sp.next[0] : sp.next;
  const next = nextRaw && nextRaw.startsWith("/admin") ? nextRaw : "/admin";

  return (
    <AuthLayout title="لوحة الإدارة" subtitle="سجّل الدخول لإدارة الديون والإعدادات">
      <AdminLoginForm next={next} />
    </AuthLayout>
  );
}
