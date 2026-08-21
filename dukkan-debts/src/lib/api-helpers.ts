import { NextResponse } from "next/server";
import { getAdminSession, type AdminSessionPayload } from "@/lib/auth";

export function jsonError(message: string, status = 400) {
  return NextResponse.json({ error: message }, { status });
}

export async function requireAdmin(): Promise<AdminSessionPayload | NextResponse> {
  const session = await getAdminSession();
  if (!session) return jsonError("غير مصرح لك — يرجى تسجيل الدخول", 401);
  return session;
}

export function isErrorResponse(value: unknown): value is NextResponse {
  return value instanceof NextResponse;
}
