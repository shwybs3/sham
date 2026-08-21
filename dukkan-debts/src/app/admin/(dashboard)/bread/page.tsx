import { AdminBreadManager } from "@/components/admin/AdminBreadManager";

export default function AdminBreadPage() {
  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-extrabold">قسم الخبز</h1>
        <p className="text-sm text-muted">متابعة تسجيلات الخبز اليومية لكل الزبائن المسجّلين عبر جوجل</p>
      </div>
      <AdminBreadManager />
    </div>
  );
}
