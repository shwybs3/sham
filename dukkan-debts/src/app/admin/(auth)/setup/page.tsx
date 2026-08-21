import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { AuthLayout } from "@/components/admin/AuthLayout";
import { AdminSetupForm } from "@/components/admin/AdminSetupForm";

export const dynamic = "force-dynamic";

export default async function SetupPage() {
  const adminCount = await prisma.adminUser.count();
  if (adminCount > 0) redirect("/admin/login");

  return (
    <AuthLayout title="إعداد لوحة الإدارة" subtitle="خطوة واحدة قبل ما تبدأ — أنشئ حساب الإدارة الأول">
      <AdminSetupForm />
    </AuthLayout>
  );
}
