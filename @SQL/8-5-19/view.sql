CREATE VIEW [_V_simple_by_date]
AS
/****** Script for SelectTopNRows command from SSMS  ******/
SELECT [id] AS [total_for_month]
     ,LEFT([export_duedate], 7) AS [rs_month]
FROM [ComAuto].[dbo].[_JobBoardDataMash]
GO

--EXEC simple_one




