import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { createAdminSession, verifyPassword } from "@/lib/auth";
import { jsonError } from "@/lib/api-helpers";

const schema = z.object({
  username: z.string().trim().min(1),
  password: z.string().min(1),
});

// Very small in-memory rate limiter to slow down brute-force login attempts.
const attempts = new Map<string, { count: number; resetAt: number }>();
const MAX_ATTEMPTS = 8;
const WINDOW_MS = 5 * 60 * 1000;

function isRateLimited(ip: string): boolean {
  const now = Date.now();
  const entry = attempts.get(ip);
  if (!entry || entry.resetAt < now) {
    attempts.set(ip, { count: 1, resetAt: now + WINDOW_MS });
    return false;
  }
  entry.count += 1;
  return entry.count > MAX_ATTEMPTS;
}

export async function POST(request: NextRequest) {
  const ip = request.headers.get("x-forwarded-for") ?? "local";
  if (isRateLimited(ip)) {
    return jsonError("محاولات كثيرة جداً، حاول لاحقاً بعد بضع دقائق", 429);
  }

  const body = await request.json().catch(() => null);
  const parsed = schema.safeParse(body);
  if (!parsed.success) {
    return jsonError("يرجى إدخال اسم المستخدم وكلمة المرور");
  }

  const { username, password } = parsed.data;
  const admin = await prisma.adminUser.findUnique({ where: { username } });
  if (!admin) {
    return jsonError("اسم المستخدم أو كلمة المرور غير صحيحة", 401);
  }

  const valid = await verifyPassword(password, admin.passwordHash);
  if (!valid) {
    return jsonError("اسم المستخدم أو كلمة المرور غير صحيحة", 401);
  }

  await createAdminSession({ adminId: admin.id, username: admin.username });
  return NextResponse.json({ ok: true });
}
