import { describe, expect, it } from 'vitest'
import { hasGroupPermission, memberCountLabel, memberRoleBadgeClass, memberRoleLabel } from '../utils/group'
import type { GroupMembership } from '../types'

describe('group utils', () => {
  it('formats member count label', () => {
    expect(memberCountLabel(1)).toBe('1 member')
    expect(memberCountLabel(7)).toBe('7 members')
  })

  it('checks limited admin permissions', () => {
    const membership: GroupMembership = {
      role: 'admin',
      permissions: { edit_info: true, add_members: false },
      is_full_admin: false,
    }

    expect(hasGroupPermission(membership, 'edit_info')).toBe(true)
    expect(hasGroupPermission(membership, 'add_members')).toBe(false)
  })

  it('grants all permissions to owner', () => {
    const membership: GroupMembership = {
      role: 'owner',
      permissions: null,
      is_full_admin: false,
    }

    expect(hasGroupPermission(membership, 'manage_admins')).toBe(true)
  })

  it('formats member role labels and badge classes', () => {
    expect(memberRoleLabel('owner')).toBe('Owner')
    expect(memberRoleLabel('admin', true)).toBe('Admin')
    expect(memberRoleLabel('admin', false)).toBe('Limited admin')
    expect(memberRoleLabel('moderator')).toBe('Moderator')
    expect(memberRoleLabel('member')).toBeNull()
    expect(memberRoleBadgeClass('owner')).toContain('chatify-member-role-badge-owner')
  })
})
