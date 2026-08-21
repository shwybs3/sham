import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { createAdminSession, hashPassword } from "@/lib/auth";
import { jsonError } from "@/lib/api-helpers";
import { SETTINGS_KEYS } from "@/lib/settings";

const schema = z.object({
  shopName: z.string().trim().min(2, "اسم الدكان قصير جداً").max(80),
  username: z
    .string()
    .trim()
    .min(3, "اسم المستخدم قصير جداً")
    .max(40)
    .regex(/^[a-zA-Z0-9_.-]+$/, "اسم المستخدم يجب أن يكون بالإنجليزية بدون مسافات"),
  password: z.string().min(6, "كلمة المرور يجب أن تكون 6 أحرف على الأقل").max(200),
});

export async function POST(request: NextRequest) {
  const existing = await prisma.adminUser.count();
  if (existing > 0) {
    return jsonError("تم إعداد الحساب مسبقاً", 409);
  }

  const body = await request.json().catch(() => null);
  const parsed = schema.safeParse(body);
  if (!parsed.success) {
    return jsonError(parsed.error.issues[0]?.message ?? "بيانات غير صالحة");
  }

  const { shopName, username, password } = parsed.data;
  const passwordHash = await hashPassword(password);

  const admin = await prisma.$transaction(async (tx) => {
    const created = await tx.adminUser.create({
      data: { username, passwordHash },
    });
    await tx.setting.upsert({
      where: { key: SETTINGS_KEYS.shopName },
      update: { value: shopName },
      create: { key: SETTINGS_KEYS.shopName, value: shopName },
    });
    return created;
  });

  await createAdminSession({ adminId: admin.id, username: admin.username });

  return NextResponse.json({ ok: true });
}
