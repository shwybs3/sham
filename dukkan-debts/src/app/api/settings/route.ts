import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { getSettings, setSettings, SETTINGS_KEYS } from "@/lib/settings";
import { jsonError, requireAdmin, isErrorResponse } from "@/lib/api-helpers";
import { prisma } from "@/lib/prisma";
import { hashPassword, verifyPassword } from "@/lib/auth";

export async function GET() {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const settings = await getSettings();
  return NextResponse.json({ settings });
}

const allowedKeys = new Set(Object.values(SETTINGS_KEYS));

const bodySchema = z.object({
  settings: z.record(z.string(), z.string()).optional(),
  passwordChange: z
    .object({
      currentPassword: z.string().min(1),
      newPassword: z.string().min(6).max(200),
    })
    .optional(),
});

export async function PATCH(request: NextRequest) {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const body = await request.json().catch(() => null);
  const parsed = bodySchema.safeParse(body);
  if (!parsed.success) return jsonError(parsed.error.issues[0]?.message ?? "بيانات غير صالحة");

  const { settings, passwordChange } = parsed.data;

  if (settings) {
    const filtered: Record<string, string> = {};
    for (const [key, value] of Object.entries(settings)) {
      if (allowedKeys.has(key as (typeof SETTINGS_KEYS)[keyof typeof SETTINGS_KEYS])) {
        filtered[key] = value;
      }
    }
    await setSettings(filtered);
  }

  if (passwordChange) {
    const adminUser = await prisma.adminUser.findUnique({ where: { id: admin.adminId } });
    if (!adminUser) return jsonError("تعذر العثور على الحساب", 404);

    const valid = await verifyPassword(passwordChange.currentPassword, adminUser.passwordHash);
    if (!valid) return jsonError("كلمة المرور الحالية غير صحيحة", 401);

    const passwordHash = await hashPassword(passwordChange.newPassword);
    await prisma.adminUser.update({ where: { id: admin.adminId }, data: { passwordHash } });
  }

  return NextResponse.json({ ok: true });
}
