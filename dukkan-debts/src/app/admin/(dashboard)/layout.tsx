import { redirect } from "next/navigation";
import { getAdminSession } from "@/lib/auth";
import { getSettings, SETTINGS_KEYS } from "@/lib/settings";
import { AdminShell } from "@/components/admin/AdminShell";

export const dynamic = "force-dynamic";

export default async function AdminDashboardLayout({ children }: { children: React.ReactNode }) {
  const session = await getAdminSession();
  if (!session) redirect("/admin/login");

  const settings = await getSettings();

  return (
    <AdminShell shopName={settings[SETTINGS_KEYS.shopName]} username={session.username}>
      {children}
    </AdminShell>
  );
}
