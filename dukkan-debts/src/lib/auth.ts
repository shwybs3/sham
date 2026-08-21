import { SignJWT, jwtVerify } from "jose";
import { cookies } from "next/headers";
import bcrypt from "bcryptjs";

const encoder = new TextEncoder();

function secretKey() {
  const secret = process.env.AUTH_SECRET;
  if (!secret) {
    throw new Error("AUTH_SECRET غير معرّف في متغيرات البيئة");
  }
  return encoder.encode(secret);
}

export const ADMIN_COOKIE = "dukkan_admin_session";
export const BREAD_COOKIE = "dukkan_bread_session";

export type AdminSessionPayload = {
  adminId: string;
  username: string;
};

export type BreadSessionPayload = {
  customerId: string;
  email: string;
  name: string;
};

async function signSession(payload: Record<string, unknown>, expiresIn: string) {
  return new SignJWT(payload)
    .setProtectedHeader({ alg: "HS256" })
    .setIssuedAt()
    .setExpirationTime(expiresIn)
    .sign(secretKey());
}

async function verifySession<T>(token: string | undefined): Promise<T | null> {
  if (!token) return null;
  try {
    const { payload } = await jwtVerify(token, secretKey());
    return payload as T;
  } catch {
    return null;
  }
}

export async function createAdminSession(payload: AdminSessionPayload) {
  const token = await signSession(payload, "30d");
  const store = await cookies();
  store.set(ADMIN_COOKIE, token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    maxAge: 60 * 60 * 24 * 30,
  });
}

export async function destroyAdminSession() {
  const store = await cookies();
  store.delete(ADMIN_COOKIE);
}

export async function getAdminSession(): Promise<AdminSessionPayload | null> {
  const store = await cookies();
  return verifySession<AdminSessionPayload>(store.get(ADMIN_COOKIE)?.value);
}

export async function createBreadSession(payload: BreadSessionPayload) {
  const token = await signSession(payload, "90d");
  const store = await cookies();
  store.set(BREAD_COOKIE, token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    maxAge: 60 * 60 * 24 * 90,
  });
}

export async function destroyBreadSession() {
  const store = await cookies();
  store.delete(BREAD_COOKIE);
}

export async function getBreadSession(): Promise<BreadSessionPayload | null> {
  const store = await cookies();
  return verifySession<BreadSessionPayload>(store.get(BREAD_COOKIE)?.value);
}

export async function hashPassword(password: string): Promise<string> {
  return bcrypt.hash(password, 12);
}

export async function verifyPassword(password: string, hash: string): Promise<boolean> {
  return bcrypt.compare(password, hash);
}

// Edge/proxy-safe verification (no cookies() API, receives raw token string)
export async function verifyTokenRaw<T>(token: string | undefined | null): Promise<T | null> {
  if (!token) return null;
  try {
    const { payload } = await jwtVerify(token, secretKey());
    return payload as T;
  } catch {
    return null;
  }
}
