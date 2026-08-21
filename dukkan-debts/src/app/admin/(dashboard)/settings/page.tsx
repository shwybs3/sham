import { ImagePlus } from "lucide-react";
import { getSettings } from "@/lib/settings";
import { prisma } from "@/lib/prisma";
import { AdminSettingsForm } from "@/components/admin/AdminSettingsForm";
import { BannersManager } from "@/components/admin/BannersManager";
import { Card } from "@/components/ui/Card";

export const dynamic = "force-dynamic";

export default async function AdminSettingsPage() {
  const [settings, banners] = await Promise.all([
    getSettings(),
    prisma.banner.findMany({ orderBy: { sortOrder: "asc" } }),
  ]);

  return (
    <div className="space-y-6">
      <div className="mx-auto max-w-2xl">
        <h1 className="text-2xl font-extrabold">الإعدادات</h1>
        <p className="text-sm text-muted">تحكم كامل بكل شيء في المنصة</p>
      </div>

      <AdminSettingsForm initialSettings={settings} />

      <div className="mx-auto max-w-2xl">
        <Card className="p-5">
          <div className="mb-4 flex items-center gap-2 font-bold">
            <ImagePlus className="size-4.5 text-primary" /> البانرات الإعلانية
          </div>
          <BannersManager initialBanners={banners} />
        </Card>
      </div>
    </div>
  );
}
