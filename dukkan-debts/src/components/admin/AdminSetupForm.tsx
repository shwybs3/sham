"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Store, User, Lock, ArrowLeft } from "lucide-react";
import { Input, Label } from "@/components/ui/Input";
import { Button } from "@/components/ui/Button";
import { apiFetch } from "@/lib/api-client";
import { useToast } from "@/components/ui/Toast";

export function AdminSetupForm() {
  const router = useRouter();
  const { push } = useToast();
  const [shopName, setShopName] = useState("");
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      await apiFetch("/api/admin/setup", {
        method: "POST",
        body: JSON.stringify({ shopName, username, password }),
      });
      push("تم إعداد الحساب بنجاح، أهلاً بك!", "success");
      router.push("/admin");
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : "حدث خطأ");
    } finally {
      setLoading(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <Label htmlFor="shopName">اسم الدكان</Label>
        <div className="relative">
          <Store className="pointer-events-none absolute right-3.5 top-1/2 size-4.5 -translate-y-1/2 text-muted" />
          <Input
            id="shopName"
            value={shopName}
            onChange={(e) => setShopName(e.target.value)}
            placeholder="بقالة الأمانة"
            className="pe-11"
            required
          />
        </div>
      </div>

      <div>
        <Label htmlFor="username">اسم المستخدم</Label>
        <div className="relative">
          <User className="pointer-events-none absolute right-3.5 top-1/2 size-4.5 -translate-y-1/2 text-muted" />
          <Input
            id="username"
            value={username}
            onChange={(e) => setUsername(e.target.value)}
            placeholder="admin"
            className="pe-11"
            dir="ltr"
            required
          />
        </div>
      </div>

      <div>
        <Label htmlFor="password">كلمة المرور</Label>
        <div className="relative">
          <Lock className="pointer-events-none absolute right-3.5 top-1/2 size-4.5 -translate-y-1/2 text-muted" />
          <Input
            id="password"
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="6 أحرف على الأقل"
            className="pe-11"
            required
            minLength={6}
          />
        </div>
      </div>

      {error && <p className="rounded-lg bg-danger-soft px-3 py-2 text-sm font-medium text-danger">{error}</p>}

      <Button type="submit" className="w-full" loading={loading}>
        <ArrowLeft className="size-4" />
        إنشاء الحساب والبدء
      </Button>
    </form>
  );
}
