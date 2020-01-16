
/****** 
--create view of with just the month number:  
CREATE VIEW _v_month_simple AS
SELECT TOP (1000) 
	   [name]
      ,[phone]
      ,[job_count]
      ,[mail_activity]
      ,[created]
	  ,REPLACE(LEFT([created], 2), '/', '') AS [month]
FROM [JobBoard].[dbo].[client_export-new2old]
WHERE [created] LIKE '%2019%'
******/


-- Script for SelectTopNRows command from SSMS 
SELECT --TOP (1000) 
	   [name]
      --,[phone]
      --,[job_count]
      --,[mail_activity]
      ,[created]
      ,[month]
	  ,MONTH([created]) AS [month_created]
	  ,YEAR([created]) AS [year_created]
FROM [JobBoard].[dbo].[_v_month_simple]
GO

-- get just the month and new clients per month
-- get just the month and new clients per month
SELECT --TOP (1000) 
	 CAST([month] AS INT) AS [month]
	,COUNt(*) AS [total_new_clients]
	,CASE
		WHEN [month] = 1 THEN 'Jan'
		WHEN [month] = 2 THEN 'Feb'
		WHEN [month] = 3 THEN 'Mar'
		WHEN [month] = 4 THEN 'Apr'
		WHEN [month] = 5 THEN 'May'
		WHEN [month] = 6 THEN 'June'
		WHEN [month] = 7 THEN 'July'
		WHEN [month] = 8 THEN 'Aug'
		WHEN [month] = 9 THEN 'Sep'
		WHEN [month] = 10 THEN 'Oct'
		WHEN [month] = 11 THEN 'Nov'
		WHEN [month] = 12 THEN 'Dec'
		ELSE [month]
	END AS [month_spelled]
FROM [JobBoard].[dbo].[_v_month_simple]
GROUP BY [month]
ORDER BY [month] ASC
GO





--